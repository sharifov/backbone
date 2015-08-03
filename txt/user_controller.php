<?

Class PRUserController extends Controller {

    private static function _setCookie($data) {
        foreach ($data as $key => $value) {
            setcookie('user[' . $key . ']', $value, time() + 3600 * 48, '/');
        }
    }

    private static function _setCookieOpenid($data) {
        foreach ($data as $key => $value) {
            setcookie('openid[' . $key . ']', $value, time() + 3600 * 48, '/');
        }
    }

    public function profiledit() {
        $user = $this->isUserLoginned();

        if ($this->user_login) {
            $warnings = array('name' => false, 'email' => false, 'email_exists' => false, 'password' => false, 'repeat_password' => false, 'new_password' => false);
            $data['name'] = ifsetor($_POST['name'], '');
            //$data['email'] = ifsetor($_POST['email'], '');
            $data['amplua'] = ifsetor($_POST['amplua'], '');
            $data['about'] = ifsetor($_POST['about'], '');
            $password = ifsetor($_POST['password'], '');
            $repeat_password = ifsetor($_POST['repeat_password'], '');
            $new_password = ifsetor($_POST['new_password'], '');
            $data['date_edit'] = $_SERVER['REQUEST_TIME'];
            $data['livejournal'] = ifsetor($_POST['livejournal'], '');
            $data['vkontakte'] = ifsetor($_POST['vkontakte'], '');
            $data['facebook'] = ifsetor($_POST['facebook'], '');
            $data['icq'] = ifsetor($_POST['icq'], '');
            $data['gtalk'] = ifsetor($_POST['gtalk'], '');
            $data['skype'] = ifsetor($_POST['skype'], '');
            $data['country_id'] = ifsetor($_POST['country_id'], 227);

            if ($this->posted()) {
                if ($data['name']) {
                    if ($new_password) {
                        if ((md5($password) == $user['password']) || $user['identity']) {
                            if (strlen($new_password) >= 3) {
                                if ($new_password == $repeat_password) {
                                    $User = new PRUserModel();
                                    $newp = array('password' => md5($new_password));
                                    $data = array_merge($newp, $data);
                                    $User->updateItems($user['id'], $data);
                                    self::_setCookie($data);
                                } else {
                                    $warnings['repeat_password'] = true;
                                }
                            } else {
                                $warnings['new_password'] = true;
                            }
                        } else {
                            if (strlen($new_password) < 3) {
                                $warnings['new_password'] = true;
                                $warnings['repeat_password'] = true;
                            } else {
                                if ($new_password != $repeat_password) {
                                    $warnings['repeat_password'] = true;
                                }
                            }
                            $warnings['password'] = true;
                        }
                    } else {
                        $User = new PRUserModel();
                        $User->updateItems($user['id'], $data);
                        self::_setCookie($data);
                    }

                    if (!$warnings['password'] && !$warnings['new_password'] && !$warnings['repeat_password']) {
                        $local_path_to_avatar = PROJECT_AVATARTMP_PATH . $user['id'] . '/' . md5($user['id']);
                        if (is_file($local_path_to_avatar)) {
                            if (!is_dir(PROJECT_AVATAR_PATH . $user['id'])) {
                                PRUtil::_mkdir(PROJECT_AVATAR_PATH . $user['id']);
                            }
                            PRUtil::moveUploadFile($local_path_to_avatar, PROJECT_AVATAR_PATH . $user['id'] . '/', $user['id'] . '.' . PRUtil::DEFAULT_EXT);
                            $User->updateItems($user['id'], array('avatar' => PROJECT_AVATAR_CUT_PATH . $user['id'] . '/' . $user['id'] . '.' . PRUtil::DEFAULT_EXT));
                            $data = array_merge(array('avatar' => PROJECT_AVATAR_CUT_PATH . $user['id'] . '/' . $user['id'] . '.' . PRUtil::DEFAULT_EXT), $data);
                            self::_setCookie($data);
                        } elseif ($_POST['delete_avatar']) {
                            $local_path_to_avatar = PROJECT_AVATAR_PATH . $user['id'] . '/' . $user['id'] . '.' . PRUtil::DEFAULT_EXT;
                            if (is_dir($local_path_to_avatar)) {
                                @unlink($local_path_to_avatar);
                            }
                            $User->updateItems($user['id'], array('avatar' => ''));
                        }
                    }
                    $llng = $_SESSION['ln'] == 2 ? 'ru/' : '';
                    $llng .= $_SESSION['ln'] == 3 ? 'en/' : '';
                    $save = true;
                    //PRUtil::redirect('/' . $llng . 'profile/edit');
                } else {
                    $warnings['name'] = $data['name'] ? false : true;
                    //$warnings['email'] = $data['email'] ? false : true;
                }
            } else {
                $data = $user;
            }
            $user = $this->isUserLoginned();

            $this->tpl->assign('save', $save);
            $this->tpl->assign('user', $user);
            $this->tpl->assign('new_password', $new_password);
            $this->tpl->assign('repeat_password', $repeat_password);
            $this->tpl->assign('password', $password);

            $this->tpl->assign('warnings', $warnings);
            $this->tpl->assign('data', $data);
        } else {
            $llng = $_SESSION['ln'] == 2 ? 'ru/' : '';
            $llng .= $_SESSION['ln'] == 3 ? 'en/' : '';
            PRUtil::redirect('/' . $llng . 'auth');
        }
    }

    public function profile() {
        $user = $this->isUserLoginned();

        if ($this->user_login) {
            $itemID = ifsetor($_GET['item'], 1);

            $Favourite = new PRModfavouriteModel();
            $Like = new PRModlikeModel();
            $Download = new PRDownloadmodModel();

            $Favouritelessons = new PRLessonfavouriteModel();
            $Lessonlike = new PRLessonlikeModel();
            $Downloadlesson = new PRDownloadlessonModel();

            $Favourite->where = array('user_id' => $user['id']);
            $Favourite->order = array('date_add' => 'desc');
            $favourites = $Favourite->getItems();

            $Like->where = array('user_id' => $user['id']);
            $Like->order = array('date_add' => 'desc');
            $likes = $Like->getItems();

            $Download->where = array('user_id' => $user['id']);
            $Download->order = array('date_add' => 'desc');
            $downloads = $Download->getItems();

            $Model = new PRModModel();
            $Model->where = array('user_id' => $user['id']);
            $Model->order = array('date_add' => 'desc');
            $userModels = $Model->getItems();

            $Pay = new PRPayModel();
            $Pay->where = array('user_id' => $user['id'], 'ik_payment_state' => 'success');
            $Pay->order = array('ik_payment_timestamp ' => 'desc');
            $Pay->changeIdFiled('model_id');
            $countPays = $Pay->getItems();

            $Favouritelessons->where = array('user_id' => $user['id']);
            $Favouritelessons->order = array('date_add' => 'desc');
            $favouritelessons = $Favouritelessons->getItems();

            $Lessonlike->where = array('user_id' => $user['id']);
            $Lessonlike->order = array('date_add' => 'desc');
            $likelessons = $Lessonlike->getItems();

            $Downloadlesson->where = array('user_id' => $user['id']);
            $Downloadlesson->order = array('date_add' => 'desc');
            $downloadlessons = $Downloadlesson->getItems();

            $Lesson = new PRLessonModel();
            $countLessons = $Lesson->getCountLessons($user['id']);

            $models = array();
            $lessons = array();
            $users = array();
            if ($itemID == 1) {
                if ($downloads) {
                    $modelsID = array_keys($downloads);
                    $Model->order = array('date_add' => 'desc');
                    $models = $Model->getItems($modelsID);
                    $usersID = array();
                    if ($models) {
                        foreach ($models as $mod) {
                            if ($mod['user_id']) {
                                $usersID[] = $mod['user_id'];
                            }
                        }
                    }

                    if ($usersID) {
                        $User = new PRUserModel();
                        $users = $User->getItems($usersID);
                    }
                }
            } elseif ($itemID == 2) {
                if ($favourites) {
                    $modelsID = array_keys($favourites);
                    $Model->order = array('date_add' => 'desc');
                    $models = $Model->getItems($modelsID);
                    $usersID = array();
                    if ($models) {
                        foreach ($models as $mod) {
                            if ($mod['user_id']) {
                                $usersID[] = $mod['user_id'];
                            }
                        }
                    }

                    if ($usersID) {
                        $User = new PRUserModel();
                        $users = $User->getItems($usersID);
                    }
                }
            } elseif ($itemID == 3) {
                if ($likes) {
                    $modelsID = array_keys($likes);
                    $Model->order = array('date_add' => 'desc');
                    $models = $Model->getItems($modelsID);
                    $usersID = array();
                    if ($models) {
                        foreach ($models as $mod) {
                            if ($mod['user_id']) {
                                $usersID[] = $mod['user_id'];
                            }
                        }
                    }

                    if ($usersID) {
                        $User = new PRUserModel();
                        $users = $User->getItems($usersID);
                    }
                }
            } elseif ($itemID == 4) {
                $keys = array_keys($countPays);
                if ($keys) {
                    $modelsAll = $Model->getItems($keys);
                    $usersID = array();
                    if ($modelsAll) {
                        foreach ($modelsAll as $mod) {
                            if ($mod['user_id']) {
                                $usersID[] = $mod['user_id'];
                            }
                        }
                    }

                    foreach ($keys as $key) {
                        $models[$key] = $modelsAll[$key];
                        $models[$key]['date_bye'] = $pays[$key]['ik_payment_timestamp'];
                    }


                    if ($usersID) {
                        $User = new PRUserModel();
                        $users = $User->getItems($usersID);
                    }
                }
            } elseif ($itemID == 6) {
                if ($favouritelessons) {
                    $lessonsID = array_keys($favouritelessons);
                    $Lesson->where = array('publish' => '1');
                    $Lesson->order = array('date_add' => 'desc');
                    $lessons = $Lesson->getItems($lessonsID);
                }
            } elseif ($itemID == 7) {
                if ($likelessons) {
                    $lessonsID = array_keys($likelessons);
                    $Lesson->where = array('publish' => '1');
                    $Lesson->order = array('date_add' => 'desc');
                    $lessons = $Lesson->getItems($lessonsID);
                }
            } elseif ($itemID == 8) {
                $Lesson->where = array('user_id' => $user['id'], 'publish' => '1');
                $Lesson->order = array('date_add' => 'desc');
                $lessons = $Lesson->getItems();
            }

            $this->tpl->assign('countPays', $countPays);
            $this->tpl->assign('users', $users);
            $this->tpl->assign('userModels', $userModels);
            $this->tpl->assign('models', $models);
            $this->tpl->assign('lessons', $lessons);
            $this->tpl->assign('itemID', $itemID);
            $this->tpl->assign('countLessons', $countLessons);
            $this->tpl->assign('favourites', $favourites);
            $this->tpl->assign('likes', $likes);
            $this->tpl->assign('downloads', $downloads);
            $this->tpl->assign('favouritelessons', $favouritelessons);
            $this->tpl->assign('likelessons', $likelessons);
            $this->tpl->assign('downloadlessons', $downloadlessons);
        } else {
            $llng = $_SESSION['ln'] == 2 ? 'ru/' : '';
            $llng .= $_SESSION['ln'] == 3 ? 'en/' : '';
            PRUtil::redirect('/' . $llng . 'auth');
        }
    }

    public function registration() {
        $this->isUserLoginned();
        if ($this->user_login) {
            $llng = $_SESSION['ln'] == 2 ? 'ru/' : '';
            $llng .= $_SESSION['ln'] == 3 ? 'en/' : '';
            PRUtil::redirect('/' . $llng);
        }

        $warnings = array('name' => false, 'name_exists' => false, 'email' => false, 'email_exists' => false, 'password' => false, 'repeat_password' => false);
        $data['name'] = ifsetor($_POST['name'], '');
        $data['email'] = ifsetor($_POST['email'], '');
        $data['password'] = ifsetor($_POST['password'], '');
        $data['repeat_password'] = ifsetor($_POST['repeat_password'], '');
        $data['date_add'] = $_SERVER['REQUEST_TIME'];
        $okrules = ifsetor($_POST['okrules'], 0);
        _e($data);
        if ($this->posted()) {
            $User = new PRUserModel;
            $check = $User->getUser($data['email'], $data['password'], true, $data['name']);

            if ($data['name'] && $data['email'] && $data['password'] && $data['password'] == $data['repeat_password'] && !$check && ($_SESSION["captcha_code"] == $_POST['captcha_code']) && $okrules) {
                unset($data['repeat_password']);
                $pass = $data['password'];
                $this->tpl->assign('name', $data['name']);
                $this->tpl->assign('login', $data['email']);
                $this->tpl->assign('pass', $pass);
                $data['password'] = md5($data['password']);
                $language = $_SESSION['ln'] == '2' ? 2 : 3;
                $data['lang'] = $language;

                $userID = $User->addItems($data);

                $this->tpl->assign('language', $language);
                $messageTpl = $this->tpl->fetch(PROJECT_TEMPLATE_PATH . 'email_registration.tpl');

                if ($_SESSION['ln'] == '2') {
                    PRUtil::email($data['email'], PRUtil::TITLE_USER_REGISTRATION, $messageTpl);
                } elseif ($_SESSION['ln'] == '3') {
                    PRUtil::email($data['email'], PRUtil::TITLE_USER_REGISTRATION_ENG, $messageTpl);
                }
                $data['id'] = $userID;
                $this->tpl->assign('data', $data);
                $llng = $_SESSION['ln'] == '2' ? 'ru/' : '';
                $llng .= $_SESSION['ln'] == '3' ? 'en/' : '';
                //$_SESSION['regthanks'] = 1;
                self::_setCookie($data);
                if ($_SESSION['back_url']) {
                    PRUtil::redirect($_SESSION['back_url']);
                } else {
                    $llng = $_SESSION['ln'] == 2 ? 'ru/' : '';
                    $llng .= $_SESSION['ln'] == 3 ? 'en/' : '';
                    PRUtil::redirect('/' . $llng . 'profile');
                }
                //PRUtil::redirect('/' . $llng . 'profile');

                exit();
            } else {
                if ($check) {
                    $warnings['email_exists'] = $data['email'] != $check['email'] ? false : true;
                    $warnings['name_exists'] = $data['name'] != $check['name'] ? false : true;
                }
                if ($_SESSION["captcha_code"] != $_POST['captcha_code']) {
                    $warnings['captcha_code'] = true;
                }
                $warnings['okrules'] = $okrules ? false : true;
                $warnings['name'] = $data['name'] ? false : true;
                $warnings['email'] = $data['email'] ? false : true;
                $warnings['password'] = $data['password'] ? false : true;
                $warnings['repeat_password'] = ($data['password'] != $data['repeat_password'] || !$data['repeat_password']) ? true : false;
            }
        }

        $this->tpl->assign('okrules', $okrules);
        $this->tpl->assign('warnings', $warnings);
        $this->tpl->assign('data', $data);
    }

    public function registrationthanks() {
        if ($_SESSION['regthanks']) {
            unset($_SESSION['regthanks']);
        } else {
            $llng = $_SESSION['ln'] == 2 ? 'ru/' : '';
            $llng .= $_SESSION['ln'] == 3 ? 'en/' : '';
            PRUtil::redirect('/' . $llng);
        }
    }

    /**
     * authorization user
     */
    public function auth() {
        $this->isUserLoginned();
        if ($this->user_login) {
            $llng = $_SESSION['ln'] == 2 ? 'ru/' : '';
            $llng .= $_SESSION['ln'] == 3 ? 'en/' : '';
            PRUtil::redirect('/' . $llng);
        }

        $warning = false;
        $data['email'] = ifsetor($_POST['email'], '');
        $data['password'] = ifsetor($_POST['password'], '');

        if ($this->posted()) {
            $User = new PRUserModel;
            $user = $User->getUser($data['email'], $data['password']);
            if ($user) {
                self::_setCookie($user);
                if ($_SESSION['back_url']) {
                    PRUtil::redirect($_SESSION['back_url']);
                } else {
                    $llng = $_SESSION['ln'] == 2 ? 'ru/' : '';
                    $llng .= $_SESSION['ln'] == 3 ? 'en/' : '';
                    PRUtil::redirect('/' . $llng);
                }
            } else {
                $warning = true;
            }
        }

        $this->tpl->assign('warning', $warning);
        $this->tpl->assign('data', $data);
    }

    public function loginwidget() {
        $skey = '9b6758a7a25cf08e8e8d4886532bee24';
        $sid = md5($skey + $_POST['token']) .
                $content = file_get_contents("http://loginza.ru/api/authinfo?token=" . $_POST['token']);
        _e($content);
        $user_decode = json_decode($content);

        //exit();
        if (($user_decode->name->first_name || $user_decode->name->full_name) && $user_decode->identity) {
            $User = new PRUserModel();
            $User->where = array('identity' => $user_decode->identity);
            $user = $User->getOneItem();

            if (!$user) {
                $name = $user_decode->name->first_name ? $user_decode->name->first_name : $user_decode->name->full_name;
                $language = $_SESSION['ln'] == '2' ? 2 : 3;
                $data['lang'] = $language;
                $userID = $User->addItems(array('lang' => $language, 'name' => $name, 'email' => $user_decode->email, 'date_add' => $_SERVER['REQUEST_TIME'], 'identity' => $user_decode->identity, 'provider' => $user_decode->provider));
                $curUser = $User->getOneItem($userID);

                self::_setCookieOpenid($curUser);
                if ($_SESSION['back_url']) {
                    PRUtil::redirect($_SESSION['back_url']);
                } else {
                    $llng = $_SESSION['ln'] == 2 ? 'ru/' : '';
                    $llng .= $_SESSION['ln'] == 3 ? 'en/' : '';
                    PRUtil::redirect('/' . $llng . 'profile');
                }
            } else {
                self::_setCookieOpenid($user);
                if ($_SESSION['back_url']) {
                    PRUtil::redirect($_SESSION['back_url']);
                } else {
                    $llng = $_SESSION['ln'] == 2 ? 'ru/' : '';
                    $llng .= $_SESSION['ln'] == 3 ? 'en/' : '';
                    PRUtil::redirect('/' . $llng . 'profile');
                }
            }
            //PRUtil::redirect('/');
        } else {
            if ($_SESSION['back_url']) {
                PRUtil::redirect($_SESSION['back_url']);
            } else {
                $llng = $_SESSION['ln'] == 2 ? 'ru/' : '';
                $llng .= $_SESSION['ln'] == 3 ? 'en/' : '';
                PRUtil::redirect('/' . $llng);
            }
        }

        exit();
    }

    /*
     * send new password on user email
     */

    public function forgetpass() {
        $data['email'] = ifsetor($_POST['email'], '');
        $warning = false;
        if ($this->posted()) {
            $User = new PRUserModel;
            $user = $User->getUser($data['email'], '', true);
            if ($user) {
                $newpass = rand(100, 100000);
                $pass = $data['password'];
                $this->tpl->assign('login', $data['email']);
                $this->tpl->assign('pass', $newpass);
                $newpass = md5($newpass);
                $language = $_SESSION['ln'] == '2' ? 2 : 3;
                $this->tpl->assign('language', $language);
                $messageTpl = $this->tpl->fetch(PROJECT_TEMPLATE_PATH . 'email_forgotpass.tpl');

                if ($_SESSION['ln'] == 2) {
                    PRUtil::email($data['email'], PRUtil::TITLE_USER_CHANGEPASS, $messageTpl);
                } elseif ($_SESSION['ln'] == 3) {
                    PRUtil::email($data['email'], PRUtil::TITLE_USER_CHANGEPASS_ENG, $messageTpl);
                }


                $User->updateItems($user['id'], array('password' => $newpass));
                //email($data['email'], 'Восстановление паролей на priceguru', $message);
                $llng = $_SESSION['ln'] == 2 ? 'ru/' : '';
                $llng .= $_SESSION['ln'] == 3 ? 'en/' : '';
                $_SESSION['passtoemail'] = 1;
                PRUtil::redirect('/' . $llng . 'passtoemail');
            } else {
                $warning = true;
                $this->tpl->assign('warning', $warning);
            }
        }
        $this->tpl->assign('data', $data);
    }

    public function passtoemail() {
        if (isset($_SESSION['passtoemail'])) {
            unset($_SESSION['passtoemail']);
        } else {
            $llng = $_SESSION['ln'] == 2 ? 'ru/' : '';
            $llng .= $_SESSION['ln'] == 3 ? 'en/' : '';
            PRUtil::redirect('/' . $llng . 'forgetpass');
        }
    }

    /**
     * logout user
     */
    public function logout() {
        setcookie('user[id]', 0, time() - 60 * 60, '/');
        setcookie('user[name]', 0, time() - 60 * 60, '/');
        setcookie('user[email]', 0, time() - 60 * 60, '/');
        setcookie('user[password]', 0, time() - 60 * 60, '/');

        setcookie('openid[identity]', 0, time() - 60 * 60, '/');
        setcookie('openid[provider]', 0, time() - 60 * 60, '/');
        $llng = $_SESSION['ln'] == 2 ? 'ru/' : '';
        $llng .= $_SESSION['ln'] == 3 ? 'en/' : '';
        PRUtil::redirect('/');
        exit();
    }

}

