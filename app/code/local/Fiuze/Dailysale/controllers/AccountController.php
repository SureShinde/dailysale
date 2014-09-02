<?php           

/**
 * Customer account controller
 *
 * @author      Mihail
 */

require_once 'Mage/Customer/controllers/AccountController.php';

class Fiuze_Dailysale_AccountController extends Mage_Customer_AccountController
{

    /**
     * Existing user page
     */
    public function forgotPasswordAction()
    {
        $this->loadLayout();

        $this->getLayout()->getBlock('forgotPassword')->setEmailValue(
            $this->_getSession()->getForgottenEmail()
        );
        $this->_getSession()->unsForgottenEmail();

        $this->_initLayoutMessages('customer/session');
        $this->renderLayout();
    }

}
