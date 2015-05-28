<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * Copyright (c) 2014 Robin McCorkell <rmccorkell@karoshi.org.uk>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
$l = \OC::$server->getL10N('files_git');

OC::$CLASSPATH['OC\Files\Storage\GitStorage'] = 'files_git/lib/git-storage.php';

# DIRTY HACK:
$paths = array (
        'TQ/Svn/StreamWrapper/FileBuffer/Factory/LogFactory',
        'TQ/Svn/StreamWrapper/FileBuffer/Factory',
        'TQ/Svn/StreamWrapper/StreamWrapper',
        'TQ/Svn/StreamWrapper/PathFactory',
        'TQ/Svn/Repository/Repository',
        'TQ/Svn/Cli/Binary',
        'TQ/Git/StreamWrapper/FileBuffer/Factory/LogFactory',
        'TQ/Git/StreamWrapper/FileBuffer/Factory',
        'TQ/Git/StreamWrapper/StreamWrapper',
        'TQ/Git/StreamWrapper/PathFactory',
        'TQ/Git/Exception',
        'TQ/Git/Repository/Repository',
        'TQ/Git/Cli/Binary',
        'TQ/Vcs/FileSystem',
        'TQ/Vcs/StreamWrapper/FileBuffer/FactoryInterface',
        'TQ/Vcs/StreamWrapper/FileBuffer/Factory/DefaultFactory',
        'TQ/Vcs/StreamWrapper/FileBuffer/Factory/CommitFactory',
        'TQ/Vcs/StreamWrapper/FileBuffer/Factory/HeadFileFactory',
        'TQ/Vcs/StreamWrapper/FileBuffer/Factory/AbstractLogFactory',
        'TQ/Vcs/StreamWrapper/FileBuffer/Factory',
        'TQ/Vcs/StreamWrapper/PathFactoryInterface',
        'TQ/Vcs/StreamWrapper/PathInformation',
        'TQ/Vcs/StreamWrapper/AbstractPathFactory',
        'TQ/Vcs/StreamWrapper/PathInformationInterface',
        'TQ/Vcs/StreamWrapper/RepositoryRegistry',
        'TQ/Vcs/StreamWrapper/AbstractStreamWrapper',
        'TQ/Vcs/Buffer/StringBuffer',
        'TQ/Vcs/Buffer/StreamBuffer',
        'TQ/Vcs/Buffer/FileBufferInterface',
        'TQ/Vcs/Buffer/ArrayBuffer',
        'TQ/Vcs/Buffer/StreamBufferException',
        'TQ/Vcs/Exception',
        'TQ/Vcs/Repository/AbstractRepository',
        'TQ/Vcs/Repository/RepositoryInterface',
        'TQ/Vcs/Repository/Transaction',
        'TQ/Vcs/Cli/CallException',
        'TQ/Vcs/Cli/Call',
        'TQ/Vcs/Cli/CallResult',
        'TQ/Vcs/Cli/Binary',
        'TQ/Vcs/Gaufrette/Adapter'
        );

$TQ = 'files_git/3rdparty/PHP-Stream-Wrapper-for-Git/src';

foreach($paths as $p) {
  $c = str_replace('/', '\\', $p);
  OC::$CLASSPATH[$c] =  "$TQ/$p.php";
}

OC_Mount_Config::registerBackend('\OC\Files\Storage\GitStorage', array(
	'backend' => (string)$l->t('Git'),
	'priority' => 150,
	'configuration' => array(
		'datadir' => (string)$l->t('GitDir')),
	'has_dependencies' => true));

