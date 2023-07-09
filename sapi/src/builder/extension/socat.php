<?php

use SwooleCli\Preprocessor;
use SwooleCli\Extension;

return function (Preprocessor $p) {
    $depends = [
        'socat'
    ];
    $ext = (new Extension('socat'))
        ->withHomePage('http://www.dest-unreach.org/socat/')
        ->withManual('http://www.dest-unreach.org/socat/')
        ->withLicense('https://repo.or.cz/socat.git/blob/refs/heads/master:/COPYING', Extension::LICENSE_LGPL);
    call_user_func_array([$ext, 'withDependentLibraries'], $depends);
    $p->addExtension($ext);
    $p->setExtHook('socat', function (Preprocessor $p) {
        $workdir = $p->getWorkDir();
        $builddir = $p->getBuildDir();
        $cmd = <<<EOF
                mkdir -p {$workdir}/bin/
                cd {$builddir}/socat
                cp -f socat {$workdir}/bin/
                cp -rf doc {$workdir}/bin/socat-docs


EOF;
        if ($p->getOsType() == 'macos') {
            $cmd .= <<<EOF
            otool -L {$workdir}/bin/socat
EOF;
        } else {
            $cmd .= <<<EOF
              file {$workdir}/bin/socat
              readelf -h {$workdir}/bin/socat
EOF;
        }
        return $cmd;
    });
};
