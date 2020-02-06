<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Method not allowed</title>
        <style type="text/css">
            /* Sanitize.css https://github.com/csstools/sanitize.css > v8.0 */
            *,::after,::before{box-sizing:border-box}::after,::before{text-decoration:inherit;vertical-align:inherit}html{cursor:default;line-height:1.5;-moz-tab-size:4;tab-size:4;-webkit-tap-highlight-color:transparent;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;word-break:break-word}body{margin:0}h1{font-size:2em;margin:.67em 0}dl dl,dl ol,dl ul,ol dl,ol ol,ol ul,ul dl,ul ol,ul ul{margin-block-start:0;margin-block-end:0}hr{height:0;overflow:visible}main{display:block}nav ol,nav ul{list-style:none;padding:0}pre{font-family:monospace,monospace;font-size:1em}a{background-color:transparent}abbr[title]{text-decoration:underline;text-decoration:underline dotted}b,strong{font-weight:bolder}code,kbd,samp{font-family:monospace,monospace;font-size:1em}small{font-size:80%}audio,canvas,iframe,img,svg,video{vertical-align:middle}audio,video{display:inline-block}audio:not([controls]){display:none;height:0}img{border-style:none}svg:not([fill]){fill:currentColor}svg:not(:root){overflow:hidden}table{border-collapse:collapse}button,input,select{margin:0}button{overflow:visible;text-transform:none}[type=button],[type=reset],[type=submit],button{-webkit-appearance:button}fieldset{padding:.35em .75em .625em}input{overflow:visible}legend{color:inherit;display:table;max-width:100%;white-space:normal}progress{display:inline-block;vertical-align:baseline}select{text-transform:none}textarea{margin:0;overflow:auto;resize:vertical}[type=checkbox],[type=radio]{padding:0}[type=search]{-webkit-appearance:textfield;outline-offset:-2px}::-webkit-inner-spin-button,::-webkit-outer-spin-button{height:auto}::-webkit-input-placeholder{color:inherit;opacity:.54}::-webkit-search-decoration{-webkit-appearance:none}::-webkit-file-upload-button{-webkit-appearance:button;font:inherit}::-moz-focus-inner{border-style:none;padding:0}:-moz-focusring{outline:1px dotted ButtonText}:-moz-ui-invalid{box-shadow:none}details{display:block}dialog{background-color:#fff;border:solid;color:#000;display:block;height:-moz-fit-content;height:-webkit-fit-content;height:fit-content;left:0;margin:auto;padding:1em;position:absolute;right:0;width:-moz-fit-content;width:-webkit-fit-content;width:fit-content}dialog:not([open]){display:none}summary{display:list-item}canvas{display:inline-block}template{display:none}[tabindex],a,area,button,input,label,select,summary,textarea{-ms-touch-action:manipulation;touch-action:manipulation}[hidden]{display:none}[aria-busy=true]{cursor:progress}[aria-controls]{cursor:pointer}[aria-disabled=true],[disabled]{cursor:not-allowed}[aria-hidden=false][hidden]{display:initial}[aria-hidden=false][hidden]:not(:focus){clip:rect(0,0,0,0);position:absolute}html{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji"}code,kbd,pre,samp{font-family:Menlo,Consolas,Roboto Mono,Ubuntu Monospace,Noto Mono,Oxygen Mono,Liberation Mono,monospace,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji"}
            /* minified default design css */
            body,html{background-color:#fff;background-image:none}body{font-size:1rem;font-weight:300;padding:1rem}code{background-color:#f2f2f2;font-size:.85em;color:#ff860b;padding:2px 4px}h1,h2,h3,h4,h5,h6{font-weight:600}.container{display:block;margin:0 auto;text-align:center;width:90%}@media (min-width:600px) and (max-width:799px){.container{width:35rem}}@media (min-width:800px) and (max-width:899px){.container{width:47rem}}@media (min-width:900px){.container{width:51.39rem}}
        </style>
    </head>
    <body>
        <div class="container">
            <h1>405 Method not allowed</h1>
            <?php
            if (isset($allowedArray) && is_array($allowedArray)) {
                $allowedString = '<code>' . implode('</code>, <code>', $allowedArray) . '</code>';
            }
            if (!isset($allowedString)) {
                $allowedString = '';
            }
            ?>
            <p>Method not allowed. Must be one of: <?php echo $allowedString; ?></p>
            <p><a href="/">Back to home</a></p>
        </div>
    </body>
</html>