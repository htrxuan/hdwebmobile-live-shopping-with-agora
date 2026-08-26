<?php
namespace htrxuan\hdlsa;

if (!defined('ABSPATH')) {
    exit;
}

require_once "AccessToken.php";

class HDLSA_RtcTokenBuilder
{
    const RoleAttendee = 0;
    const RolePublisher = 1;
    const RoleSubscriber = 2;
    const RoleAdmin = 101;

    public static function buildTokenWithUid($appID, $appCertificate, $channelName, $uid, $role, $privilegeExpireTs)
    {
        return HDLSA_RtcTokenBuilder::buildTokenWithUserAccount($appID, $appCertificate, $channelName, $uid, $role, $privilegeExpireTs);
    }

    public static function buildTokenWithUserAccount($appID, $appCertificate, $channelName, $userAccount, $role, $privilegeExpireTs)
    {
        $token = HDLSA_AccessToken::init($appID, $appCertificate, $channelName, $userAccount);
        $Privileges = HDLSA_AccessToken::Privileges;
        $token->addPrivilege($Privileges["kJoinChannel"], $privilegeExpireTs);
        if (
            ($role == HDLSA_RtcTokenBuilder::RoleAttendee) ||
            ($role == HDLSA_RtcTokenBuilder::RolePublisher) ||
            ($role == HDLSA_RtcTokenBuilder::RoleAdmin)
        ) {
            $token->addPrivilege($Privileges["kPublishVideoStream"], $privilegeExpireTs);
            $token->addPrivilege($Privileges["kPublishAudioStream"], $privilegeExpireTs);
            $token->addPrivilege($Privileges["kPublishDataStream"], $privilegeExpireTs);
        }
        return $token->build();
    }
}
