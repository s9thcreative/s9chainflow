<@@sendframe_tmpl
_sitetitle "ERROR"
@>
<style>
.errorbox{
	padding:4em 0;
}
.errorbox .in{
	font-size:130%;
	border:0.3em solid #808080;
	max-width:800px; margin:0 auto;
	box-shadow:0 1em 1em rgba(0,0,0,0.2);
}
.errorbox .title{
	text-align:center; font-weight:bold;
	background-color:#808080; color:#fff;
}
.errorbox .body{
	padding:1.5em;
}
body.sp .errorbox .body{
	padding:0.7em;
}
</style>
<div class="page_normal">
<div class="errorbox">
<div class="in">
<div class="title">ERROR</div>
<div class="body">
<@@if $errmsg@><@$errmsg@><@@else@>エラーが発生しました<@/if@>
</div>
</div>
</div>
</div>

