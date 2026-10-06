<?php /** Shared <head>. Expects $pageTitle (optional) and $pageDescription (optional). */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(isset($pageTitle) ? $pageTitle . ' · ' . config('app.name') : config('app.name') . ' — ' . config('app.tagline')) ?></title>
    <meta name="description" content="<?= e($pageDescription ?? 'Official website of FC Scorpio: fixtures, squad, match tickets and the official fan store.') ?>">
    <meta name="theme-color" content="#070c18">
    <link rel="icon" type="image/png" href="<?= asset('img/crest.png') ?>">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
