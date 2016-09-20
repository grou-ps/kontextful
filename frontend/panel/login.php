<?php
/**
 * Login
 */
session_start();
if($_POST["op"]=="login"){
    if($_POST["username"] == "adm" && $_POST["pass"] == "adm"){
        $_SESSION["admin"] = "ok";
        header("Location:index.php");
        exit;
    } else {
        echo "<div align='center'><font color='#FF0000'>Login error!</font></div>";
    }
}
?>
<html>
<head>
<style>
    body, html, table{
        font-family: "Verdana";
        font-size: 12px;
    }
</style>
</head>
<body>
    <div align="center">
        <form name="loginfrm" method="POST" action="login.php">
            <table width="200" border="0">
                <tr>
                    <td colspan="2"><h2>Kontextful</h2><!--<img src="images/logo.png">--></td>
                </tr>
                <tr>
                    <td width="%40"><strong>Username</strong></td>
                    <td width="%60"><input type="text" name="username" id="username"></td>
                </tr>
                <tr>
                    <td><strong>Password</strong></td>
                    <td><input type="password" name="pass" id="pass"></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td><input type="submit" value="Login"></td>
                </tr>
            </table>
            <input type="hidden" name="op" value="login">
        </form>
    </div>
</body>
</html>



