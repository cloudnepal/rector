<?php

namespace RectorPrefix20210905;

if (\class_exists('tx_install_session')) {
    return;
}
class tx_install_session
{
}
\class_alias('tx_install_session', 'tx_install_session', \false);
