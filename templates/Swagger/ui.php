<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>SwaggerUI - MindForge API</title>
    <link rel="stylesheet" href="<?= $this->Url->build('/css/vendor/swagger-ui/swagger-ui.css') ?>" />
</head>
<body>
<div id="swagger-ui" data-spec-url="<?= h($this->Url->build(['action' => 'json', '_full' => true])) ?>"></div>
<script src="<?= $this->Url->build('/js/vendor/swagger-ui/swagger-ui-bundle.js') ?>" crossorigin></script>
<script src="<?= $this->Url->build('/js/vendor/swagger-ui/swagger-ui-standalone-preset.js') ?>" crossorigin></script>
<?= $this->Html->script('swagger_ui_init') ?>
</body>
</html>
