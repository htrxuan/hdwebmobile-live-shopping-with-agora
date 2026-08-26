<?php
namespace htrxuan\hdlsa;

if (!defined('ABSPATH')) {
    exit;
}

require_once "AccessToken.php";

class HDLSA_RtmTokenBuilder
{
    const RoleRtmUser = 1;

    public static function buildToken($appID, $appCertificate, $userAccount, $role, $privilegeExpireTs)
    {
        $token = HDLSA_AccessToken::init($appID, $appCertificate, $userAccount, "");
        $Privileges = HDLSA_AccessToken::Privileges;
        $token->addPrivilege($Privileges["kRtmLogin"], $privilegeExpireTs);
        return $token->build();
    }
}