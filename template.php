<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<link rel="stylesheet" type="text/css" href="FBro.css">
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
		<script src="./?do=getVars"></script>
		<script src="FBro.js"></script>
    </head>
    <body>
        <div id="top">

			<div id="create_folder_form">
                <form action="?" method="post" id="mkdir">
                    <label for=dirname>Create New Folder</label><input id=dirname type=text name=name value="" />
                    <input type="submit" value="create" />
                </form>
			</div>

			<div id="file_drop_target">
				Drag Files Here To Upload
				<b>or</b>
				<input type="file" multiple />
			</div>

            <div id="breadcrumb">&nbsp;</div>

        </div>

        <div id="upload_progress"></div>
        <table id="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Size</th>
                    <th>Modified</th>
                    <th>Permissions</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="list">
            </tbody>
        </table>

        <footer>simple php filemanager by <a href="https://github.com/jcampbell1">jcampbell1</a> &amp; <a href="https://github.com/Djoulzy">Djoulzy</a></footer>
    </body>
</html>