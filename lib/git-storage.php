<?php
/**
 * Copyright (c) 2012 Vitas Stradal <vitas@matfyz.com>
 * This file is licensed under the GPL version 2.
 */

namespace OC\Files\Storage;

use TQ\Git\Repository\Repository;
use TQ\Git\Cli\Binary;

class GitStorage extends \OC\Files\Storage\StreamWrapper{

        protected $datadir;
        protected $repo;


        public function __construct($arguments) {
                $this->datadir = $arguments['datadir'];
                if (substr($this->datadir, -1) !== '/') {
                        $this->datadir .= '/';
                }
                $this->repo = Repository::open($this->datadir);
                #error_log("git store, jupi");
        }

        public function __destruct() {
        }

        public function getId() {
                #error_log("git store, G1");
                return 'git::' . $this->datadir;
        }

        # OK
        public function mkdir($path) {
                #error_log("git store, G2 $path");
                return @mkdir($this->getSourcePath($path), 0777, true);
        }

        # FIXME (ok)
        public function rmdir($path) {
                $this->unlink($path);
                $this->repo->commit("rmdir by oc", null, $this->getAuthor());
        }

        # OK
        public function opendir($path) {
                #return opendir($this->getSourcePath($path));
                #error_log("git store: opendir($path)");

		$path = trim($path, '/');
                $repo_files = $this->repo->listDirectory($path);
                $files = array();
                foreach($repo_files as $file ) {
                  if( $file != '.git' ) {
                    $files[] = $file;
                  }
                }

                #$files = array("anketa.wiki");
                \OC\Files\Stream\Dir::register('git'.$path, $files);
                return opendir('fakedir://git'.$path);
        }

        # ok
        public function is_dir($path) {
                if (substr($path, -1) == '/') {
                        $path = substr($path, 0, -1);
                }
                return is_dir($this->getSourcePath($path));
        }

        # ok
        public function is_file($path) {
                return is_file($this->getSourcePath($path));
        }

        # ok
        public function stat($path) {
                #error_log("git store, G6 $path"); pouziva se
                clearstatcache();
                $fullPath = $this->getSourcePath($path);
                $statResult = stat($fullPath);
                if (PHP_INT_SIZE === 4 && !$this->is_dir($path)) {
                        $filesize = $this->filesize($path);
                        $statResult['size'] = $filesize;
                        $statResult[7] = $filesize;
                }
                return $statResult;
        }

        # ok
        public function filetype($path) {
                #error_log("git store, G6 $path");
                $filetype = filetype($this->getSourcePath($path));
                if ($filetype == 'link') {
                        $filetype = filetype(realpath($this->getSourcePath($path)));
                }
                return $filetype;
        }

        # ok
        public function filesize($path) {
                #error_log("git store, G7 $path"); pouziva se
                if ($this->is_dir($path)) {
                        return 0;
                }
                $fullPath = $this->getSourcePath($path);
                if (PHP_INT_SIZE === 4) {
                        $helper = new \OC\LargeFileHelper;
                        return $helper->getFilesize($fullPath);
                }
                return filesize($fullPath);
        }

        # ok
        public function isReadable($path) {
                $src = $this->getSourcePath($path);
                $ret = is_readable($this->getSourcePath($path));
                return $ret;
        }

        # ok
        public function isUpdatable($path) {
                return is_writable($this->getSourcePath($path));
        }

        # ok
        public function file_exists($path) {
                return file_exists($this->getSourcePath($path));
        }

        # ok
        public function filemtime($path) {
                clearstatcache($this->getSourcePath($path));
                return filemtime($this->getSourcePath($path));
        }

        public function touch($path, $mtime = null) {
                // sets the modification time of the file to the given value.
                // If mtime is nil the current time is set.
                // note that the access time of the file always changes to the current time.
                #error_log("git store, G12 $path touch");
                if ($this->file_exists($path) and !$this->isUpdatable($path)) {
                        return false;
                }
                if (!is_null($mtime)) {
                        $result = touch($this->getSourcePath($path), $mtime);
                } else {
                        $result = touch($this->getSourcePath($path));
                }
                if ($result) {
                        clearstatcache(true, $this->getSourcePath($path));
                }
                $this->repo->add(array($path));
                $this->repo->commit("touch by oc", null, $this->getAuthor());
                return $result;
        }

        # ok
        public function file_get_contents($path) {
                #error_log("git store, slurp  $path");
                if( $this->isTempFile($path1)) {
			return file_get_contents($this->getSourcePath($path));
                }
                return $this->repo->showFile($path);
        }

        # ok
        public function file_put_contents($path, $data) {
                #error_log("git store, spew  $path");
                if( $this->isTempFile($path)) {
			return file_put_contents($this->getSourcePath($path), $data);
                }
                return $this->repo->writeFile($path, $data,
                                                "updated by oc",     # message
                                                null, null,          # file mode, dir mode
                                                true,                # recursive
                                                $this->getAuthor()); #  author
        }

        public function unlink($path) {
                #error_log("git store, rm $path");
                if( $this->isTempFile($path)) {
                        # fs unlink
			if ($this->is_dir($path)) {
				return $this->rmdir($path);
			} else if ($this->is_file($path)) {
				return unlink($this->getSourcePath($path));
			} else {
				return false;
			}
                }
                else {
                  return $this->repo->removeFile($path, "removed by oc", true, true, $this->getAuthor());
                }

        }

        #FIXME (ok)
        public function rename($path1, $path2) {
                error_log("git store, rename $path1 => $path2");
                $is_tmp1 = $this->isTempFile($path1);
                $is_tmp2 = $this->isTempFile($path2);

                if( $is_tmp1 ) {
		  if(!rename($this->getSourcePath($path1), $this->getSourcePath($path2))) {
                        return false;
                  }
                  if( !$is_tmp1 ) {
                      $this->repo->add(array($path2));
                      $this->repo->commit("created by oc", null, $this->getAuthor());
                  }
                }
                if( ! $is_tmp1 ) {
                        if( $is_tmp2 ) {
                                return false;
                        }
                        return $this->repo->renameFile($path1, $path2, "moved by oc", true, $this->getAuthor());

                }

		if( ! rename($this->getSourcePath($path1), $this->getSourcePath($path2)) ) {
                        return false;
                }
                $this->add(array($path2));
                return $this->repo->commit("moved by oc", array($path2), $this->getAuthor());
        }

        #FIXME (ok)
        public function copy($path1, $path2) {
                #error_log("git store, cp $path1 => $path2");
                $is_tmp1 = $this->isTempFile($path1);
                $is_tmp2 = $this->isTempFile($path2);
                if( $is_tmp2 ) {
                        return $this->local_copy($path1, $path2);
                }

                $this->local_copy($path1, $path2);
                $this->add(array($path2));
                return $this->repo->commit("copyied by oc", array($path2), $this->getAuthor());
        }

        # ok
        public function fopen($path, $mode) {
                #FIXME, if mode si 'w' and so on, commit is missin
                error_log("git store: fopen($path, $mode)");
                return fopen($this->getSourcePath($path), $mode);
        }

        # ok
        public function hash($type, $path, $raw = false) {
                #error_log("git store, G15 $path");
                return hash_file($type, $this->getSourcePath($path), $raw);
        }

        # ok
        public function free_space($path) {
                $space = @disk_free_space($this->getSourcePath($path));
                if ($space === false || is_null($space)) {
                        return \OCP\Files\FileInfo::SPACE_UNKNOWN;
                }
                return $space;
        }

        # ok
        public function search($query) {
                return $this->searchInDir($query);
        }

        # ok
        public function getLocalFile($path) {
                return $this->getSourcePath($path);
        }

        # ok
        public function getLocalFolder($path) {
                return $this->getSourcePath($path);
        }

        /**
         * @param string $query
         */
        protected function searchInDir($query, $dir = '') {
                $files = array();
                $physicalDir = $this->getSourcePath($dir);
                foreach (scandir($physicalDir) as $item) {
                        if ($item == '.' || $item == '..')
                                continue;
                        $physicalItem = $physicalDir . '/' . $item;

                        if (strstr(strtolower($item), strtolower($query)) !== false) {
                                $files[] = $dir . '/' . $item;
                        }
                        if (is_dir($physicalItem)) {
                                $files = array_merge($files, $this->searchInDir($query, $dir . '/' . $item));
                        }
                }
                return $files;
        }

        /**
         * check if a file or folder has been updated since $time
         *
         * @param string $path
         * @param int $time
         * @return bool
         */
        # FIXME: not sure, if this is correct
        public function hasUpdated($path, $time) {
                #return true;
                if ($this->file_exists($path)) {
                        return $this->filemtime($path) > $time;
                } else {
                        return true;
                }
        }

        /**
         * Get the source path (on disk) of a given path
         *
         * @param string $path
         * @return string
         */
        protected function getSourcePath($path) {
                $fullPath = $this->datadir . $path;
                return $fullPath;
        }

        /**
         * {@inheritdoc}
         */
        public function isLocal() {
                ## FIXME: bude pouzivat open nebo slurp (maybe)
                return false;
        }

        /**
         * get the ETag for a file or folder
         *
         * @param string $path
         * @return string
         */
        # FIXME: sha1 from gitu
        public function getETag($path) {
                if ($this->is_file($path)) {
                        $stat = $this->stat($path);
                        return md5(
                                $stat['mtime'] .
                                $stat['ino'] .
                                $stat['dev'] .
                                $stat['size']
                        );
                } else {
                        return parent::getETag($path);
                }
        }


	/**
	 * @param string $target
	 */
        # snad OK
	public function uploadFile($local_path, $target) {
		if( !copy($local_path, $this->constructUrl($target)) ) {
                        return false;
                }
                if( $this->isTempFile($target) ) {
                        $this->add(array($target));
                        return $this->repo->commit("uploaded by oc", array($target), $this->getAuthor());
                }
                return true;
	}
	/**
	 * check if php-ftp is installed
	 */
        # OK
	public static function checkDependencies() {
		if ( Binary::locateBinary() ) {
			return(true);
		} else {
			return array('git');
		}
	}

	/**
	 * @param string $path
	 * @return string|null
	 */
         # FIXME: what is that for?
	public function constructUrl($path) {
                error_log("git store, G27 $path");
          return 'git://' . $this->getSourcePath($path);
        }
        protected function isTempFile($path) {
          return preg_match('/\.ocTransferId\d+\.part$/', $path);
        }
        protected function getAuthor() {
                return \OC_User::getUser();
        }
        protected function local_copy($path1, $path2) {
		if ($this->is_dir($path1)) {
                        # this is common
                        return false;
                        # return parent::copy($path1, $path2);
		} else {
			return copy($this->getSourcePath($path1), $this->getSourcePath($path2));
		}
        }
}
