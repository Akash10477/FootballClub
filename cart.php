<?php
require __DIR__ . '/includes/bootstrap.php';

/* ---------- Cart actions ---------- */
if (is_post()) {
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['product_id'] ?? 0);

    if ($action === 'add' || $action === 'update') {
        $product = db_one('SELECT id, name, stock FROM products WHERE id = ? AND is_active = 1', [$id]);
        if (!$product) {
            flash('error', 'Product not found.');
            redirect('shop.php');
        }
        $qty = max(0, (int) ($_POST['quantity'] ?? 1));
        if ($action === 'add') {
            $qty += cart()[$id] ?? 0;
        }
        if ($qty > (int) $product['stock']) {
            $qty = (int) $product['stock'];
            flash('info', "Only {$product['stock']} of {$product['name']} available. We've adjusted your cart.");
        } elseif ($action === 'add') {
            flash('success', "{$product['name']} added to your cart.");
        }
        cart_set($id, $qty);
    } elseif ($action === 'remove') {
        cart_set($id, 0);
    } elseif ($action === 'checkout') {
        $user = require_login();
        $name = input('shipping_name');
        $phone = input('shipping_phone');
        $address = input('shipping_address');

        $errors = [];
        if (mb_strlen($name) < 2)                      $errors[] = 'Please enter the recipient name.';
        if (!preg_match('/^\+?[0-9\s-]{10,15}$/', $phone)) $errors[] = 'Please enter a valid phone number.';
        if (mb_strlen($address) < 10)                  $errors[] = 'Please enter a full delivery address.';
        if (!cart())                                   $errors[] = 'Your cart is empty.';

        if (!$errors) {
            try {
                $ref = db_transaction(function (PDO $pdo) use ($user, $name, $phone, $address) {
                    $total = 0;
                    $items = [];
                    foreach (cart() as $pid => $qty) {
                        // Lock each product row while we check and reduce stock.
                        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? AND is_active = 1 FOR UPDATE');
                        $stmt->execute([$pid]);
                        $p = $stmt->fetch();
                        if (!$p || $p['stock'] < $qty) {
                            throw new RuntimeException(($p['name'] ?? 'An item') . ' no longer has enough stock. Please review your cart.');
                        }
                        $items[] = [$p, $qty];
                        $total += $p['price'] * $qty;
                    }
                    $total += delivery_fee($total);
                    $ref = reference('ORD');
                    $pdo->prepare('INSERT INTO orders (reference, user_id, total, shipping_name, shipping_phone, shipping_address) VALUES (?, ?, ?, ?, ?, ?)')
                        ->execute([$ref, $user['id'], $total, $name, $phone, $address]);
                    $orderId = (int) $pdo->lastInsertId();
                    foreach ($items as [$p, $qty]) {
                        $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity) VALUES (?, ?, ?, ?, ?)')
                            ->execute([$orderId, $p['id'], $p['name'], $p['price'], $qty]);
                        $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?')->execute([$qty, $p['id']]);
                    }
                    return $ref;
                });
                cart_clear();
                flash('success', "Order $ref placed! We'll call you to confirm delivery.");
                redirect('account.php#orders');
            } catch (RuntimeException $e) {
                $errors[] = $e->getMessage();
            }
        }
        $_SESSION['checkout_errors'] = $errors;
        $_SESSION['checkout_old'] = compact('name', 'phone', 'address');
    }
    redirect('cart.php');
}

$lines    = cart_lines();
$subtotal = array_sum(array_column($lines, 'subtotal'));
$delivery = $lines ? delivery_fee($subtotal) : 0;
$user     = current_user();
$errors   = $_SESSION['checkout_errors'] ?? [];
$oldCo    = $_SESSION['checkout_old'] ?? [];
unset($_SESSION['checkout_errors'], $_SESSION['checkout_old']);

$pageTitle = 'Your cart';
$active = 'shop';
require __DIR__ . '/partials/header.php';
?>
<section class="section">
    <div class="container">
        <div class="section-head"><div><div class="eyebrow">Official store</div><h2>Your cart</h2></div></div>

        <?php if (!$lines): ?>
            <div class="card empty">
                <?= icon('cart') ?>
                <p>Your cart is empty.</p>
                <a class="btn btn-primary" href="<?= url('shop.php') ?>">Browse the shop</a>
            </div>
        <?php else: ?>
        <div class="cart-layout">
            <div class="card" style="padding:8px 24px">
                <?php foreach ($lines as $l): $p = $l['product']; ?>
                    <div class="cart-line">
                        <img src="<?= e(product_image($p['image'])) ?>" alt="">
                        <div>
                            <a href="<?= url('product.php?id=' . (int) $p['id']) ?>"><strong><?= e($p['name']) ?></strong></a>
                            <div class="muted" style="font-size:.85rem"><?= money($p['price']) ?> each</div>
                        </div>
                        <form method="post" style="display:flex;gap:8px;align-items:center">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                            <div class="qty">
                                <button type="button" data-step="-1" aria-label="Fewer"><?= icon('minus') ?></button>
                                <input type="number" name="quantity" value="<?= (int) $l['qty'] ?>" min="1" max="<?= (int) $p['stock'] ?>" onchange="this.form.submit()" aria-label="Quantity">
                                <button type="button" data-step="1" aria-label="More"><?= icon('plus') ?></button>
                            </div>
                        </form>
                        <div style="display:flex;align-items:center;gap:10px">
                            <strong class="nowrap"><?= money($l['subtotal']) ?></strong>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                                <button class="icon-btn" type="submit" aria-label="Remove"><?= icon('trash') ?></button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <aside class="card sticky">
                <h3>Order summary</h3>
                <div class="summary-row"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
                <div class="summary-row"><span>Delivery</span><span><?= $delivery ? money($delivery) : 'Free' ?></span></div>
                <?php if ($delivery): ?><div class="faint" style="font-size:.8rem">Free delivery on orders over <?= money(FREE_DELIVERY_FROM) ?></div><?php endif; ?>
                <div class="summary-row total"><span>Total</span><span><?= money($subtotal + $delivery) ?></span></div>

                <?php if (!$user): ?>
                    <a class="btn btn-primary btn-block" style="margin-top:16px" href="<?= url('login.php?next=' . urlencode(url('cart.php'))) ?>">Sign in to checkout</a>
                <?php else: ?>
                    <form method="post" class="form-grid" style="margin-top:18px">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="checkout">
                        <?php if ($errors): ?>
                            <div class="alert alert-error"><?= icon('alert') ?><div><?= implode('<br>', array_map('e', $errors)) ?></div></div>
                        <?php endif; ?>
                        <div class="field"><label for="sn">Recipient name</label>
                            <input class="input" id="sn" name="shipping_name" required value="<?= e($oldCo['name'] ?? $user['name']) ?>"></div>
                        <div class="field"><label for="sp">Phone</label>
                            <input class="input" id="sp" name="shipping_phone" required value="<?= e($oldCo['phone'] ?? $user['phone']) ?>"></div>
                        <div class="field"><label for="sa">Delivery address</label>
                            <textarea class="textarea" id="sa" name="shipping_address" required style="min-height:80px"><?= e($oldCo['address'] ?? '') ?></textarea></div>
                        <button class="btn btn-primary btn-block" type="submit">Place order · Cash on delivery</button>
                    </form>
                <?php endif; ?>
            </aside>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
