<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title><?=$title?></title>
	<meta name="description" content="The small framework with powerful features">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="shortcut icon" type="image/png" href="/favicon.ico"/>

	<!-- STYLES -->

	<style {csp-style-nonce}>
		* {
			transition: background-color 300ms ease, color 300ms ease;
		}
		*:focus {
			background-color: rgba(221, 72, 20, .2);
			outline: none;
		}
		html, body {
			color: rgba(33, 37, 41, 1);
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
			font-size: 16px;
			margin: 0;
			padding: 0;
			-webkit-font-smoothing: antialiased;
			-moz-osx-font-smoothing: grayscale;
			text-rendering: optimizeLegibility;
		}
		section {
			margin: 0 auto;
			max-width: 1100px;
			padding: 15rem 1.75rem 3.5rem 1.5rem;
		}
	</style>
</head>
<body hidden="true">
	<section>
		<center style="vertical-align: middle;">
			<?=$btn?>
		</center>
	</section>

	<!-- jQuery -->
    <script src="<?=base_url('/plugins/jquery/jquery.min.js');?>"></script>
    <script>
    	$(function () {
            $("document").ready(function () {
                $('input[type="submit"]').click();
            });
        });
    </script>
</body>
</html>
