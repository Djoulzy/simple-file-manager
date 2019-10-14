
<!DOCTYPE html>
<html><head>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<link rel="stylesheet" type="text/css" href="app.css">
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
<script src="table.js"></script>

<script>
$(function() {
	var XSRF = (document.cookie.match('(^|; )_sfm_xsrf=([^;]*)')||0)[2];
	var MAX_UPLOAD_SIZE = <?=$this->getMaxUpFile() ?>;
	var $tbody = $('#list');
    var allow_direct_link = <?=$this->AllowDirectLink() ?>;

	$(window).on('hashchange',list).trigger('hashchange');
	$('#table').tablesorter();

	$('#table').on('click','.delete',function(data) {
		$.post("",{'do':'delete',file:$(this).attr('data-file'),xsrf:XSRF},function(response){
			list();
		},'json');
		return false;
	});

	$('#mkdir').submit(function(e) {
		var hashval = decodeURIComponent(window.location.hash.substr(1)),
			$dir = $(this).find('[name=name]');
		e.preventDefault();
		$dir.val().length && $.post('?',{'do':'mkdir',name:$dir.val(),xsrf:XSRF,file:hashval},function(data){
			list();
		},'json');
		$dir.val('');
		return false;
	});
})
</script>
<script src="tools.js"></script>

<?php if($this->AllowUpload()): ?>
	<script src="upload.js"></script>
<?php endif; ?>

</head><body>
<div id="top">
   <?php if($allow_create_folder): ?>
	<form action="?" method="post" id="mkdir" />
		<label for=dirname>Create New Folder</label><input id=dirname type=text name=name value="" />
		<input type="submit" value="create" />
	</form>

   <?php endif; ?>

   <?php if($this->AllowUpload()): ?>

	<div id="file_drop_target">
		Drag Files Here To Upload
		<b>or</b>
		<input type="file" multiple />
	</div>
   <?php endif; ?>
	<div id="breadcrumb">&nbsp;</div>
</div>

<div id="upload_progress"></div>
<table id="table"><thead><tr>
	<th>Name</th>
	<th>Size</th>
	<th>Modified</th>
	<th>Permissions</th>
	<th>Actions</th>
</tr></thead><tbody id="list">

</tbody></table>
<footer>simple php filemanager by <a href="https://github.com/jcampbell1">jcampbell1</a></footer>
</body></html>

