<html lang="{$language}" dir="ltr">
<head>
	<title>{$page_title}</title>
{if $SF->isDisplayed('base')}
	<base href="http://{$smarty.server['SERVER_NAME']}">
{/if}
	{$meta}
	<link rel="shortcut icon" href="/templates/Graffiti/images/favicon.ico">
	{$css}
</head>