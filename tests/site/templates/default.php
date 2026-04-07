<!doctype html>
<html lang="<?= $kirby->language()?->code() ?? 'en' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php snippet('seo/head', ['page' => $page]) ?>
</head>
<body>
<main>
    <h1><?= $page->title()->escape() ?></h1>
    <?= $page->text()->kirbytext() ?>
</main>
</body>
</html>
