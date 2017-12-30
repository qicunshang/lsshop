<div class="page">
    <table class="table tb-type2 msg">
        <tbody class="noborder">
        <tr>
            <td rowspan="5" class="msgbg"></td>
            <td class="tip"><?php echo $output['arr']['msg']?></td>
        </tr>
        <tr>
            <td class="tip2">若不选择将自动跳转</td>
        </tr>
        <tr>
            <td>
                <a href="<?php echo $arr['url']?>" class="btns"><span>返回</span></a>
                <script type="text/javascript"> window.setTimeout("javascript:location.href='<?php echo $output["arr"]["url"]?>'", 2000); </script>
            </td>
        </tr>
        </tbody>
    </table>
</div>