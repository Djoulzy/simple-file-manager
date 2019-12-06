<?php
/********************************
Simple PHP File Manager
Copyright John Campbell (jcampbell1)

Liscense: MIT
********************************/

class FBro
{
	private $start_dir;
	private $MAX_UPLOAD_SIZE;
	private $allow_delete;
	private $allow_upload;
	private $allow_create_folder;
	private $allow_direct_link;
	private $allow_show_folders;

	private $disallowed_extensions;
	private $hidden_extensions;

	public function __construct($start_dir)
	{
		$this->start_dir = $start_dir;

		//Disable error report for undefined superglobals
		error_reporting( error_reporting() & ~E_NOTICE );

		//Security options
		$this->allow_delete = true; // Set to false to disable delete button and delete POST request.
		$this->allow_upload = true; // Set to true to allow upload files
		$this->allow_create_folder = true; // Set to false to disable folder creation
		$this->allow_direct_link = true; // Set to false to only allow downloads and not direct link
		$this->allow_show_folders = true; // Set to false to hide all subdirectories

		$this->disallowed_extensions = ['php'];  // must be an array. Extensions disallowed to be uploaded
		$this->hidden_extensions = ['php']; // must be an array of lowercase file extensions. Extensions hidden in directory index

		$this->MAX_UPLOAD_SIZE = min(self::asBytes(ini_get('post_max_size')), self::asBytes(ini_get('upload_max_filesize')));

		$PASSWORD = '';  // Set the password, to access the file manager... (optional)

		if($PASSWORD)
		{
			session_start();
			if(!$_SESSION['_sfm_allowed']) {
				// sha1, and random bytes to thwart timing attacks.  Not meant as secure hashing.
				$t = bin2hex(openssl_random_pseudo_bytes(10));
				if($_POST['p'] && sha1($t.$_POST['p']) === sha1($t.$PASSWORD)) {
					$_SESSION['_sfm_allowed'] = true;
					header('Location: ?');
				}
				echo '<html><body><form action=? method=post>PASSWORD:<input type=password name=p autofocus/></form></body></html>';
				exit;
			}
		}

		// must be in UTF-8 or `basename` doesn't work
		setlocale(LC_ALL,'en_US.UTF-8');
		
		if(DIRECTORY_SEPARATOR==='\\') $this->start_dir = str_replace('/', DIRECTORY_SEPARATOR, $this->start_dir);
		if (substr($this->start_dir, -1) == DIRECTORY_SEPARATOR) $this->start_dir = rtrim($this->start_dir, DIRECTORY_SEPARATOR);
		if ($_REQUEST['file'][0] == DIRECTORY_SEPARATOR) $_REQUEST['file'] = ltrim($_REQUEST['file'], DIRECTORY_SEPARATOR);
		$tmp = $this->start_dir .DIRECTORY_SEPARATOR. $_REQUEST['file'];
		
		self::logger('CHROOT: '.$this->start_dir.' - CWD: '.$_REQUEST['file']);
		self::logger('Target: '.$tmp);

		if($tmp === false)
			self::err(404,'File or Directory Not Found');
		if(substr($tmp, 0,strlen($this->start_dir)) !== $this->start_dir)
			self::err(403,"Forbidden1");
		if(strpos($_REQUEST['file'], DIRECTORY_SEPARATOR) === 0)
			self::err(403,"Forbidden2");
		
		if(!$_COOKIE['_sfm_xsrf'])
			setcookie('_sfm_xsrf',bin2hex(openssl_random_pseudo_bytes(16)));
		if($_POST) {
			if($_COOKIE['_sfm_xsrf'] !== $_POST['xsrf'] || !$_POST['xsrf'])
				self::err(403,"XSRF Failure");
		}

		if (isset($_REQUEST['do']) && !empty($_REQUEST['do'])) {
			$this->actions($tmp);
			return true;
		}
		else include "template.php";
	}

	public function getJSVar() {
		$tmp = "\n// Injected PHP vars\n";
		$tmp .= 'var MAX_UPLOAD_SIZE = '.$this->getMaxUpFile()."\n";
		$tmp .= 'var ALLOW_DIRECT_LINK = '.$this->AllowDirectLink()."\n";
		$tmp .= "// End PHP vars\n\n";

		return $tmp;
	}

	static public function logger($mess) {
		$fd = fopen('/var/www/simple-file-manager/app.log', 'a');
		fwrite($fd, sprintf("%s : %s\n", date('Y/m/d H:i:s'), $mess));
		fclose($fd);
	}

	public static function is_entry_ignored($entry, $allow_show_folders, $hidden_extensions)
	{
		if ($entry === basename(__FILE__)) {
			return true;
		}
	
		if (is_dir($entry) && !$allow_show_folders) {
			return true;
		}
	
		$ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
		if (in_array($ext, $hidden_extensions)) {
			return true;
		}
	
		return false;
	}

	// from: http://php.net/manual/en/function.realpath.php#84012
	public static function get_absolute_path($path)
	{
		$path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
		$parts = explode(DIRECTORY_SEPARATOR, $path);
		$absolutes = [];
		foreach ($parts as $part) {
			if ('.' == $part) continue;
			if ('..' == $part) {
				array_pop($absolutes);
			} else {
				$absolutes[] = $part;
			}
		}
		return implode(DIRECTORY_SEPARATOR, $absolutes);
	}

	public static function err($code,$msg)
	{
		http_response_code($code);
		echo json_encode(['error' => ['code'=>intval($code), 'msg' => $msg]]);
		exit;
	}

	public static function asBytes($ini_v)
	{
		$ini_v = trim($ini_v);
		$s = ['g'=> 1<<30, 'm' => 1<<20, 'k' => 1<<10];
		return intval($ini_v) * ($s[strtolower(substr($ini_v,-1))] ?: 1);
	}

	public static function rmrf($dir)
	{
		if(is_dir($dir)) {
			$files = array_diff(scandir($dir), ['.','..']);
			foreach ($files as $file)
				rmrf("$dir/$file");
			rmdir($dir);
		} else {
			unlink($dir);
		}
	}

	public static function is_recursively_deleteable($d)
	{
		$stack = [$d];
		while($dir = array_pop($stack)) {
			if(!is_readable($dir) || !is_writable($dir))
				return false;
			$files = array_diff(scandir($dir), ['.','..']);
			foreach($files as $file) if(is_dir($file)) {
				$stack[] = "$dir/$file";
			}
		}
		return true;
	}

	private function list($file)
	{
		self::logger('List: '.$file);
		if (is_dir($file)) {
			$directory = $file;
			$result = [];
			$files = array_diff(scandir($directory), ['.','..']);
			foreach ($files as $entry) if (!self::is_entry_ignored($entry, $this->allow_show_folders, $this->hidden_extensions)) {
			$i = $directory .DIRECTORY_SEPARATOR. $entry;
			$stat = stat($i);
				$result[] = [
					'mtime' => $stat['mtime'],
					'size' => $stat['size'],
					'name' => basename($i),
					'path' => preg_replace('@^\./@', '', str_replace($this->start_dir.'/', '', $i)),
					// 'path' => preg_replace('@^\./@', '', $i),
					'is_dir' => is_dir($i),
					'is_deleteable' => $this->allow_delete && ((!is_dir($i) && is_writable($directory)) ||
						(is_dir($i) && is_writable($directory) && self::is_recursively_deleteable($i))),
					'is_readable' => is_readable($i),
					'is_writable' => is_writable($i),
					'is_executable' => is_executable($i),
				];
			}
		} else {
			self::err(412,"Not a Directory");
		}
		echo json_encode(['success' => true, 'is_writable' => is_writable($file), 'results' =>$result]);
	}

	private function delete($file)
	{
		if($allow_delete) {
			rmrf($file);
		}
	}

	private function mkdir($file)
	{
		// don't allow actions outside root. we also filter out slashes to catch args like './../outside'
		$dir = $_POST['name'];
		$dir = str_replace('/', '', $dir);
		if(substr($dir, 0, 2) === '..')
			exit;
		chdir($file);
		@mkdir($_POST['name']);
	}

	private function upload($file)
	{
		foreach($disallowed_extensions as $ext)
		if(preg_match(sprintf('/\.%s$/',preg_quote($ext)), $_FILES['file_data']['name']))
			self::err(403,"Files of this type are not allowed.");

		$res = move_uploaded_file($_FILES['file_data']['tmp_name'], $file.'/'.$_FILES['file_data']['name']);
	}

	private function download($file)
	{
		$filename = basename($file);
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		header('Content-Type: ' . finfo_file($finfo, $file));
		header('Content-Length: '. filesize($file));
		header(sprintf('Content-Disposition: attachment; filename=%s',
			strpos('MSIE',$_SERVER['HTTP_REFERER']) ? rawurlencode($filename) : "\"$filename\"" ));
		ob_flush();
		readfile($file);
	}

	public function getMaxUpFile() {
		return $this->MAX_UPLOAD_SIZE;
	}

	public function AllowDirectLink() {
		if ($this->allow_direct_link) return 'true';
		else return 'false';
	}

	public function AllowUpload() {
		return $this->allow_upload;
	}

	public function AllowCreateFolder() {
		return $this->allow_create_folder;
	}

	private function actions($file)
	{
		// if (!empty($_REQUEST['file'])) $file = $_REQUEST['file'];
		// else $file = $this->start_dir;
		
		self::logger('Action: '.$file);
		if($_GET['do'] == 'list') {
			$this->list($file);
		} elseif ($_POST['do'] == 'delete') {
			$this->delete($file);
		} elseif ($_POST['do'] == 'mkdir' && $allow_create_folder) {
			$this->mkdir($file);
		} elseif ($_POST['do'] == 'upload' && $allow_upload) {
			$this->upload($file);
		} elseif ($_GET['do'] == 'download') {
			$this->download($file);
		}
	}
}
?>
