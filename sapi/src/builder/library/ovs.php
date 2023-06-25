<?php

use SwooleCli\Library;
use SwooleCli\Preprocessor;

return function (Preprocessor $p) {
    $ovs_prefix = OVS_PREFIX;
    $lib = new Library('ovs');
    $lib->withHomePage('https://github.com/openvswitch/ovs/')
        ->withLicense('https://github.com/openvswitch/ovs/blob/master/LICENSE', Library::LICENSE_APACHE2)
        ->withUrl('https://github.com/openvswitch/ovs/archive/refs/tags/v3.1.1.tar.gz')
        ->withFile('ovs-v3.1.1.tar.gz')
        ->withManual('https://github.com/openvswitch/ovs/blob/v3.1.1/Documentation/intro/install/general.rst')
        ->withPrefix($ovs_prefix)
        ->withBuildCached(false)
        ->withCleanBuildDirectory()
        ->withCleanPreInstallDirectory($ovs_prefix)
        ->withBuildScript(
            <<<EOF
        set -x
        ./boot.sh
        ./configure --help
        PACKAGES="openssl"
        CPPFLAGS="$(pkg-config  --cflags-only-I --static \$PACKAGES ) " \
        LDFLAGS="$(pkg-config   --libs-only-L   --static \$PACKAGES ) " \
        LIBS="$(pkg-config      --libs-only-l   --static \$PACKAGES ) " \
        ./configure \
        --prefix={$ovs_prefix} \
        --enable-ssl \
        --enable-shared=no \
        --enable-static=yes
        make -j {$p->maxJob}
        make install
        # apk add mandoc man-pages
        # apk add ghostscript
        # pip3 install -i https://pypi.tuna.tsinghua.edu.cn/simple
        # pipenv --python 3
        # pipenv shell
        # export PIPENV_PYPI_MIRROR=https://pypi.tuna.tsinghua.edu.cn/simple
        # pipenv install -r Documentation/requirements.txt
        # pipenv install jinja2==3.0.0

        make dist-docs -j {$p->maxJob}
        make docs-check -j {$p->maxJob}
EOF
        )
        ->withMakeOptions( " dist-docs ")
        ->withPkgName('libofproto')
        ->withPkgName('libopenvswitch')
        ->withPkgName('libovsdb')
        ->withPkgName('libsflow')
        ->withBinPath($ovs_prefix . '/bin/')
        ->withDependentLibraries('openssl')
    ;

    $p->addLibrary($lib);
};
