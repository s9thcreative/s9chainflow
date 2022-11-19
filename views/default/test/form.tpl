<h1>TEST/Form</h1>
<form action="/test/form/send" method="POST">
<@@fillin data="data"@>
name:<input type="text" name="name"><br>
<div><@$msg.name@></div>
email:<input type="text" name="email"><br>
<div><@$msg.email@></div>
pass:<input type="password" name="password">
<div><@$msg.password@></div>
conf:<input type="password" name="password_conf">
<div><@$msg.password_conf@></div>
<@/fillin@>
<input type="submit">
</form>