<?php
namespace Core\Overridefiles\Block\Customer\Account;
class AuthorizationLink extends \Magento\Customer\Block\Account\AuthorizationLink
{
    public function getLabel() {
        return $this->isLoggedIn() ? __('Logout') : __('Login');
    }
    public function getcheckLoggedin(){
    	return $this->isLoggedIn() ? 1 : 0;
    }
    
}
