<?php
/**
 * Hello my friend! Welcome to my code. I hope you like it. Good luck!
 * I did not put a lot of comments here, but i added a lot of PHPDOC.
 * As you can see, everything in English here, because i sure, only programmers will read that.
 * Sure, they knew English.
 *
 * @author TimLiz#2952
 */

namespace run;

use event;
use Exception;

require_once(__DIR__."/events.php");
require_once(__DIR__."/event.php");
require_once(__DIR__."/aliases.php");
require_once(__DIR__."/user.php");
require_once(__DIR__."/api/request.php");
require_once(__DIR__."/html/parser.php");
require_once(__DIR__."/api/message.php");
require_once(__DIR__."/repository/message.php");
require_once(__DIR__."/repository/user.php");
require_once(__DIR__."/repository/watching.php");
require_once(__DIR__."/repository/payment.php");
require_once(__DIR__."/builders/messageBuilder.php");
require_once(__DIR__."/builders/lotBuilder.php");

class run extends aliases {
    /**
     * @var int SETTINGS_DISABLE_MESSAGE_CHECK Should bot stop reading messages
     */
    const SETTINGS_DISABLE_MESSAGE_CHECK = 1;

    public events $events;
    public user $user;
    public message $message;
    public array $users;

    /**
     * @var string The golden key
     */
    static public string $goldenKey;

    public static run $runner;

    /**
     * @param array $settings Settings for runner
     * @throws Exception
     */
    public function __construct(array $settings = array()) {
        //Checking for extensions
        if (!extension_loaded("curl")) throw new Exception("Curl extension is not loaded!");
        if (!extension_loaded("json")) throw new Exception("Json extension is not loaded!");

        //Making empty screen
        echo chr(27) . chr(91) . 'H' . chr(27) . chr(91) . 'J';

        //Checking is needs set up
        if (!file_exists(__DIR__."/../config.json")) {
            echo "Checking is bot configured...".PHP_EOL;
            require_once (__DIR__."/setup.php");
            setup::run();
        }

        $config = json_decode(file_get_contents(__DIR__."/../config.json"), true);

        self::$goldenKey = $config["key"];

        //Creating folder for temp files
        if (!file_exists(__DIR__."/../temp")) {
            echo "Creating temp folder...".PHP_EOL;
            mkdir(__DIR__."/../temp");
        }

        //Folder for orders temp files
        if (!file_exists(__DIR__."/../temp/payments")) {
            echo "Checking payments...".PHP_EOL;
            mkdir(__DIR__."/../temp/payments");
        }

        $this->events = new events($this);
        $this->user = new user($settings, $this);
        $this->message = new message($this);
        self::$runner = $this;
        $this->users = array();
    }

    public function getUser(int $ID, string $username):userRepository {
        if (!isset($this->users[$ID])) {
            $return = new userRepository($username, $ID, $this);
            $this->users[$ID] = $return;
        } else {
            $return = $this->users[$ID];
        }

        return $return;
    }

    public function run() {
        echo chr(27) . chr(91) . 'H' . chr(27) . chr(91) . 'J';
        echo "Ready!".PHP_EOL;
        while (true) {
            if (!isset($this->user->settings[self::SETTINGS_DISABLE_MESSAGE_CHECK]) || !$this->user->settings[self::SETTINGS_DISABLE_MESSAGE_CHECK]) {
                $msg = $this->message->checkForMsg();
                if ($msg && $msg->author->answered) {
                    if ($msg->author->ID != $this->user->ID) {
                        $msg->author->answered = false;
                        $this->events->fireEvent(event::message, $msg);
                        $msg->author->answered = true;
                    } else {
                        $this->events->fireEvent(event::youreMessage, $msg);
                    }
                }
            }

            if ($this->user->checkForOrders()) {
                $payment = paymentRepository::new();

                if ($payment) {
                    $this->events->fireEvent(event::payment, $payment);
                }
            }

            $this->user->rise();

            sleep(3);
        }
    }
}