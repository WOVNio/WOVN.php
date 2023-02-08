<html>
    <head>
        <meta charset="utf-8">
        <title>test</title>
    </head>
    <body>
        <!--#include virtual="/ssi_included.php?foo=1&bar=2" -->
        <!--#include virtual="/ssi_included.php" -->
        <?php 
            echo "request query: foo=" . $_GET['foo']; 
        ?>
        <h1>This is a test page using SSI</h1>
    </body>
</html>
