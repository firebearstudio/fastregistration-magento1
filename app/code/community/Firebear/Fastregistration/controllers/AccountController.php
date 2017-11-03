<?php
/**
 * Rewrite Customer account controller
 *
 * @category   Firebear
 * @package    Firebear_Fastregistration
 * @copyright  Copyright (c) 2013 Magento <fbeardev@gmail.com>
 */

require_once 'Mage/Customer/controllers/AccountController.php';

class Firebear_Fastregistration_AccountController extends Mage_Customer_AccountController
{
    /**
     * Create customer account action
     */
    public function createPostAction()
    {
        if(Mage::getStoreConfig('fastregistration/general/enabled')){

            $session = $this->_getSession();
            if ($session->isLoggedIn()) {
                $this->_redirect('*/*/');
                return;
            }
            $session->setEscapeMessages(true); // prevent XSS injection in user input
            if ($this->getRequest()->isPost()) {
                $errors = array();

                if (!$customer = Mage::registry('current_customer')) {
                    $customer = Mage::getModel('customer/customer')->setId(null);
                }

                /* @var $customerForm Mage_Customer_Model_Form */
                $customerForm = Mage::getModel('customer/form');
                $customerForm->setFormCode('customer_account_create')
                    ->setEntity($customer);

                $customerData = $customerForm->extractData($this->getRequest());

                /**
                 * Initialize customer group id
                 */
                $customer->getGroupId();

                $password = $this->getRequest()->getPost('password');
                if(!Mage::getStoreConfig('fastregistration/general/show_password')){
                    $password = Mage::helper('core')->getRandomString(8,
                        Mage_Core_Helper_Data::CHARS_PASSWORD_LOWERS
                        . Mage_Core_Helper_Data::CHARS_PASSWORD_UPPERS
                        . Mage_Core_Helper_Data::CHARS_PASSWORD_DIGITS
                        . Mage_Core_Helper_Data::CHARS_PASSWORD_SPECIALS);
                }
                try {
                    $customerErrors = $customerForm->validateData($customerData);
                    if ($customerErrors !== true) {
                        $errors = array_merge($customerErrors, $errors);
                    } else {
                        $customerForm->compactData($customerData);
                        $customer->setPassword($password);
                        $customer->setConfirmation($password);
                    }

                    $validationResult = count($errors) == 0;

                    if (true === $validationResult) {
                        $customer->save();

                        Mage::dispatchEvent('customer_register_success',
                            array('account_controller' => $this, 'customer' => $customer)
                        );

                        if ($customer->isConfirmationRequired()) {
                            $customer->sendNewAccountEmail(
                                'confirmation',
                                $session->getBeforeAuthUrl(),
                                Mage::app()->getStore()->getId()
                            );
                            $session->addSuccess($this->__('Account confirmation is required. Please, check your email for the confirmation link. To resend the confirmation email please <a href="%s">click here</a>.', Mage::helper('customer')->getEmailConfirmationUrl($customer->getEmail())));
                            $this->_redirectSuccess(Mage::getUrl('*/*/index', array('_secure'=>true)));
                            return;
                        } else {
                            $session->setCustomerAsLoggedIn($customer);
                            $url = $this->_welcomeCustomer($customer);
                            $this->_redirectSuccess($url);
                            return;
                        }
                    } else {
                        $session->setCustomerFormData($this->getRequest()->getPost());
                        if (is_array($errors)) {
                            foreach ($errors as $errorMessage) {
                                $session->addError($errorMessage);
                            }
                        } else {
                            $session->addError($this->__('Invalid customer data'));
                        }
                    }
                } catch (Mage_Core_Exception $e) {
                    $session->setCustomerFormData($this->getRequest()->getPost());
                    if ($e->getCode() === Mage_Customer_Model_Customer::EXCEPTION_EMAIL_EXISTS) {
                        $url = Mage::getUrl('customer/account/forgotpassword');
                        $message = $this->__('There is already an account with this email address. If you are sure that it is your email address, <a href="%s">click here</a> to get your password and access your account.', $url);
                        $session->setEscapeMessages(false);
                    } else {
                        $message = $e->getMessage();
                    }
                    $session->addError($message);
                } catch (Exception $e) {
                    $session->setCustomerFormData($this->getRequest()->getPost())
                        ->addException($e, $this->__('Cannot save the customer.'));
                }
            }

            $this->_redirectError(Mage::getUrl('*/*/create', array('_secure' => true)));

        }else{
            parent::createPostAction();
        }
    }
}
