<?php

/**
 * Single-page app display and processing.
 */
class CRM_Anoncheckin_Extern_App_StaffInfo extends CRM_Anoncheckin_Extern_App {
  function run() {

    // For staff info, we should show the current device as a QR code and as a table.
    $deviceInfo = $this->device;
    // Add deviceStatus label to $deviceInfo.
    $optionValues = CRM_Core_OptionGroup::values('anoncheckin_device_status');
    $deviceInfo['deviceStatus'] = $optionValues[$deviceInfo['deviceStatusId']];
    $deviceInfo['participantName'] = $this->participant['displayName'];
    $this->assign('device', $deviceInfo);
    
    $deviceQrData = "anoncheckin_deviceKey:" . $this->device['deviceKey'];
    $deviceQrUrl = CRM_Anoncheckin_Utils_Qr::getQrImageUrl($deviceQrData);
    $this->assign('deviceQrUrl', $deviceQrUrl);
    $this->assign('isStaffInfo', TRUE);
  
    parent::run();
  }
}
