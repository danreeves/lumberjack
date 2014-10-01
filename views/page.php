<?php
/**
 * Page Views
 *
 * Usage:
 * Calls a function based on the slug of a page, in the style of `[slug]_view`
 * Hyphens are converted into underscores automatically,
 * e.g. case-studies -> case_studies_view
 *
 * Global context changes should be made either after Initial Set Up in
 * this file, or in the `functions.php` file.
 *
 * Page specific context goes in the its `_view` function below.
 *
 * You have access to the $context and the $post ($context['post']).
 *
 */

function sample_page_view($context) {
	Timber::render('page/home.twig', $context);
}

function generic_view ($context) {
	Timber::render('page/generic.twig', $context);
}




// DO NOT DO ANYTHING AFTER THIS!
$view = str_replace('-', '_',$post->slug.'_view');
if (function_exists ($view)) {
	call_user_func($view, $context);
}
else {
	call_user_func('generic_view', $context);
}
