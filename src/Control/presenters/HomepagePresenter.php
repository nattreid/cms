<?php

namespace NAttreid\Crm\Control;

use NAttreid\AppManager\Info,
    NAttreid\Tracking\Tracking;

/**
 * Domovska stranka
 * 
 * @author Attreid <attreid@gmail.com>
 */
class HomepagePresenter extends CrmPresenter {

    /** @var Info */
    private $info;

    /** @var Tracking */
    private $tracking;

    public function __construct(Info $info, Tracking $tracking = NULL) {
        $this->info = $info;
        $this->tracking = $tracking;
    }

    public function renderDefault() {
        $template = $this->template;

        if ($this->user->isAllowed('main.homepage.info', 'view')) {
            $template->viewInfo = TRUE;

            $template->ip = $this->info->getIp();

            if (($load = $this->info->getLoad())) {
                $template->load = $load;
            }

            if ($this->tracking !== NULL) {
                $template->onlineUsers = $this->tracking->onlineUsers();
            }
        } else {
            $template->viewInfo = FALSE;
        }
    }

}
