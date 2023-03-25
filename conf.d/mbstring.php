<?php

use SwooleCli\Library;
use SwooleCli\Preprocessor;
use SwooleCli\Extension;

return function (Preprocessor $p) {
    $p->setExportVarable('ONIG_CFLAGS', '$(pkg-config --cflags --static oniguruma)');
    $p->setExportVarable('ONIG_LIBS', '$(pkg-config   --libs   --static oniguruma)');
    $p->addExtension(
        (new Extension('mbstring'))
            ->withOptions('--enable-mbstring')
            ->depends('oniguruma')
    );
};
