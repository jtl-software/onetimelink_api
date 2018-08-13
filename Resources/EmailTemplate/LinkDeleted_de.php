<?php
$tagStyle = <<<EOT
    border: 1px solid #2a3e4e;
    background: #324350;
    color: #FEFEFE;
    font-size: 12px;
    display: inline-block;
    padding: 2px 5px 1px 6px;
    margin-right: 5px;
    border-radius: 0 3px 8px 3px;
EOT;
?>
<html>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<body style="font-family: Proxima, Verdana, Helvetica, sans-serif; color:#324350">
<div style="margin: auto; align-content: center; width: 100%; max-width:1024px; padding: 0;">
    <div style="display: flex; align-items: center; background: #FFFFFF; border-bottom: 1px solid #ddd; padding:20px 0 25px 25px;">
        <!--         <div style="width:50%"><img src="--><?//=$_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']?><!--/images/JTL-logo.png" alt="JTL-OneTimeLink" height="44"/></div>-->
        <div style="width:50%"><img src="https://otl.jtl-software.de/pics/JTL-logo.png" alt="JTL-OneTimeLink" height="44"/></div>
        <span style="width:50%"><div style="text-align:right; padding-right:25px;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="height:20px; fill:#324350"><path d="M256 0c88.366 0 160 71.634 160 160s-71.634 160-160 160S96 248.366 96 160 167.634 0 256 0zm183.283 333.821l-71.313-17.828c-74.923 53.89-165.738 41.864-223.94 0l-71.313 17.828C29.981 344.505 0 382.903 0 426.955V464c0 26.51 21.49 48 48 48h416c26.51 0 48-21.49 48-48v-37.045c0-44.052-29.981-82.45-72.717-93.134z"/></svg> <?= ucfirst($data->user) ?></div></span>
    </div>
    <div style="padding: 0 25px;">
        <table style="margin-bottom:40px; margin-top:50px; font-size:20px;">
            <tr>
                <td>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="height:28px; float:left; fill:#324350"><path d="M256 8C119.043 8 8 119.083 8 256c0 136.997 111.043 248 248 248s248-111.003 248-248C504 119.083 392.957 8 256 8zm0 110c23.196 0 42 18.804 42 42s-18.804 42-42 42-42-18.804-42-42 18.804-42 42-42zm56 254c0 6.627-5.373 12-12 12h-88c-6.627 0-12-5.373-12-12v-24c0-6.627 5.373-12 12-12h12v-64h-12c-6.627 0-12-5.373-12-12v-24c0-6.627 5.373-12 12-12h64c6.627 0 12 5.373 12 12v100h12c6.627 0 12 5.373 12 12v24z"/><!--[if gte mso 12]><img src="http://wfarm3.dataknet.com/static/resources/icons/set112/d2a3b53a.png" height="28" style="float:left;"/><![endif]--></svg>
                </td>
                <td></td>
                <td><div style="padding-bottom: 20px; border-left: 1px solid #dcdcdc; padding-left: 20px;margin-left: 20px; height:80px;">&nbsp;<div><div>&nbsp;</div>
                </td>
                <td>
                    <strong>Hallo <?= ucfirst($data->user) ?>.</strong><br/> <br/>
                    Ihr OneTimeLink wurde aufgerufen.
                </td>
            </tr>
        </table>
        <div>
            <div style="clear:both">
                <div style="
                        background-color: rgba(221, 221, 221, 0.34);
                        padding-top: 20px;
                        padding-bottom: 20px;
                        padding-left: 5px;
                ">
                    <?php
                    $tagsUsed = null;
                    foreach ($data->tags as $tag) {
                        $tagsUsed .= "<span style='{$tagStyle}'>{$tag}</span>\n";
                    }
                    echo "<strong style='color: #797b80;'>Benutzte Tags:</strong>\n" . ($tagsUsed ?? "keine");
                    ?>
                </div>

                <span style="color: #797b80">
                    <strong>Zeit: </strong>&nbsp;<?= date('c') ?><br/>
                    <strong>Client IP (maskiert):</strong>&nbsp;<?= $data->ip ?><br/>
                    <strong>Useragent:</strong>&nbsp;<?= $data->useragent ?><br/>
                </span>
            </div>
        </div>
    </div>
    <div style="border-top: 1px solid #ddd; height:40px; padding:20px 0 0 25px; margin-top:80px">
        JTL-Software-GmbH
    </div>
    <div>&nbsp;</div>
</div>
</body>
</html>