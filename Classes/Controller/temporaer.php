
        // Create new pages here?
        $pageRecord = BackendUtility::getRecord('pages', $this->pObj->id, 'uid', ' AND ' . $this->getBackendUser()->getPagePermsClause(8));
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $menuItems = $pageRepository->getMenu($this->pObj->id, '*', 'sorting', '', false);
        if (is_array($pageRecord)) {
            $data = GeneralUtility::_GP('data');
            if (is_array($data['pages'])) {
                if (GeneralUtility::_GP('createInListEnd')) {
                    $endI = end($menuItems);
                    $thePid = -(int)$endI['uid'];
                    if (!$thePid) {
                        $thePid = $this->pObj->id;
                    }
                } else {
                    $thePid = $this->pObj->id;
                }
                $firstRecord = true;
                $previousIdentifier = '';
                foreach ($data['pages'] as $identifier => $dat) {
                    if (!trim($dat['title'])) {
                        unset($data['pages'][$identifier]);
                    } else {
                        $data['pages'][$identifier]['hidden'] = GeneralUtility::_GP('hidePages') ? 1 : 0;
                        $data['pages'][$identifier]['nav_hide'] = GeneralUtility::_GP('hidePagesInMenus') ? 1 : 0;
                        if ($firstRecord) {
                            $firstRecord = false;
                            $data['pages'][$identifier]['pid'] = $thePid;
                        } else {
                            $data['pages'][$identifier]['pid'] = '-' . $previousIdentifier;
                        }
                        $previousIdentifier = $identifier;
                    }
                }
                if (!empty($data['pages'])) {
                    reset($data);
                    $dataHandler = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
                    // set default TCA values specific for the user
                    $TCAdefaultOverride = $this->getBackendUser()->getTSConfigProp('TCAdefaults');
                    if (is_array($TCAdefaultOverride)) {
                        $dataHandler->setDefaultsFromUserTS($TCAdefaultOverride);
                    }
                    $dataHandler->start($data, []);
                    $dataHandler->process_datamap();
                    BackendUtility::setUpdateSignal('updatePageTree');
                    $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, '', $this->getLanguageService()->getLL('wiz_newPages_create'));
                } else {
                    $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, '', $this->getLanguageService()->getLL('wiz_newPages_noCreate'), FlashMessage::ERROR);
                }
                /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
                $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
                $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                $defaultFlashMessageQueue->enqueue($flashMessage);
                // Display result:
                $menuItems = $pageRepository->getMenu($this->pObj->id, '*', 'sorting', '', false);
                $pageLines = [];
                foreach ($menuItems as $record) {
                    BackendUtility::workspaceOL('pages', $record);
                    if (is_array($record)) {
                        $line = [];
                        $line['titleAttribute'] = BackendUtility::titleAttribForPages($record, '', false);
                        $line['title'] = GeneralUtility::fixed_lgd_cs($record['title'], $this->getBackendUser()->uc['titleLen']);
                        $line['record'] = $record;
                        $pageLines[] = $line;
                    }
                }
                $assigns['pages'] = $pageLines;
            } else {
                // Display create form
                $assigns['typeSelect'] = $this->getTypeSelectData();
            }
        } else {
            $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, '', $this->getLanguageService()->getLL('wiz_newPages_errorMsg1'), FlashMessage::ERROR);
            /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }
        // CSH
        $assigns['cshItem'] = BackendUtility::cshItem('_MOD_web_func', 'tx_wizardcrpages');

        // Rendering of the output via fluid
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
            'EXT:' . IMPORT_EXT . '/Resources/Private/Templates/CreatePagesWizard.html'
        ));
        $view->assignMultiple($assigns);
        $out = $view->render();


