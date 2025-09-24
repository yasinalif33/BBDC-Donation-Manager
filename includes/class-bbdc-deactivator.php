<?php
class BBDC_DM_Deactivator {
    public static function deactivate() {
        remove_role('volunteer');
        remove_role('bbdc_admin');
        remove_role('blood_response');
    }
}