<?php           

/**
 * Customer account controller
 *
 * @author      Mihail
 */

require_once 'Mage/Customer/controllers/AccountController.php';

class Fiuze_Dailysale_AccountController extends Mage_Customer_AccountController
{

    public function preDispatch()
    {
        // a brute-force protection here would be nice

        //parent::preDispatch();

        if (!$this->getRequest()->isDispatched()) {
            return;
        }

        $action = $this->getRequest()->getActionName();
        $openActions = array(
            'create',
            'login',
            'logoutsuccess',
            'forgotpassword',
            'forgotpasswordpost',
            'resetpassword',
            'resetpasswordpost',
            'confirm',
            'confirmation',
			'existinguser'
        );
        $pattern = '/^(' . implode('|', $openActions) . ')/i';

        if (!preg_match($pattern, $action)) {
            if (!$this->_getSession()->authenticate($this)) {
                $this->setFlag('', 'no-dispatch', true);
            }
        } else {
            $this->_getSession()->setNoReferer(true);
        }
    }

    /**
     * Existing user action
     */
    public function existingUserAction()
    {   
        $this->loadLayout();

        $this->getLayout()->getBlock('existingUser')->setEmailValue(
            $this->_getSession()->getForgottenEmail()
        );
        $this->_getSession()->unsForgottenEmail();

        $this->_initLayoutMessages('customer/session');
        $this->renderLayout();
    }

}
