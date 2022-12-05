<?php
/**
 * Hello my friend! Welcome to my code. I hope you like it. Good luck!
 * I did not put a lot of comments here, but I added a lot of PHPDOC.
 * As you can see, everything in English here, because i sure, only programmers will read that.
 * Sure, they knew English.
 *
 * @author TimLiz#2952
 */

namespace run;

use Closure;
use Error;
use event;
use Exception;


require_once(__DIR__ . "/events.php");
require_once(__DIR__ . "/aliases.php");
require_once(__DIR__ . "/user.php");
require_once(__DIR__ . "/api/request.php");
require_once(__DIR__ . "/html/parser.php");
require_once(__DIR__ . "/api/message.php");
require_once(__DIR__ . "/repository/message.php");
require_once(__DIR__ . "/repository/user.php");
require_once(__DIR__ . "/repository/watching.php");
require_once(__DIR__ . "/repository/lot.php");
require_once(__DIR__ . "/repository/payment.php");
require_once(__DIR__ . "/builders/messageBuilder.php");
require_once(__DIR__ . "/builders/lotBuilder.php");
require_once(__DIR__ . "/enums/event.php");
require_once(__DIR__ . "/timer.php");


class run extends aliases
{
    /**
     * @var int SETTINGS_DISABLE_MESSAGE_CHECK Should bot stop reading messages
     */
    const SETTINGS_DISABLE_MESSAGE_CHECK = 1;

    /**
     * @var int SETTINGS_DISABLE_LOT_RISE Should bot not rising offers
     */
    const SETTINGS_DISABLE_LOT_RISE = 2;

    /**
     * @var int SETTINGS_CUSTOM_LOOP Disables while true built in loop, you need to call tick() manually
     */
    const SETTINGS_CUSTOM_LOOP = 3;

    /**
     * @var int SETTINGS_DO_NOT_CLEAR_CONSOLE Disables console clean-up(stops cleaning logs like "Loading user...") on Ready
     */
    const SETTINGS_DO_NOT_CLEAR_CONSOLE = 4;

    /**
     * @var int SETTINGS_GOLDEN_KEY If set, bot will ignore config.json and use it from here
     */
    const SETTINGS_GOLDEN_KEY = 5;

    /**
     * @var int SETTINGS_OUTPUTFUNCTION Set it if u want to use your own output function. This works before ANY output.
     */
    const SETTINGS_OUTPUTFUNCTION = 6;

    /**
     * @var events This object is used for events to work.
     */
    public events $events;

    /**
     * @var user This object is YOU(Your account)
     */
    public user $user;

    /**
     * @var message This object is used for message functions to work
     */
    public message $message;

    /**
     * @var timer This object is used for timer functions to work
     */
    public timer $timers;

    /**
     * @var array Users are contained here(do not use this, this property is system only)
     */
    public array $users;

    /**
     * @var bool Is bot ready bool
     */
    public bool $isReady = false;

    /**
     * @var bool Is custom loop enabled bool
     */
    public bool $customLoop = false;

    /**
     * @var string The golden key
     */
    static public string $goldenKey;
    public static run $runner;

    /**
     * @var Closure This is output function. You can change it to your own function. Or remove it totaly.
     */
    public Closure $outputClosure;

    /**
     * @param array $settings Settings for runner
     * @throws Exception
     */
    public function __construct(array $settings = array()) {
        // Checking for extensions
        if (!extension_loaded("curl")) throw new Exception("Curl extension is not loaded!");
        if (!extension_loaded("json")) throw new Exception("Json extension is not loaded!");

        // Declaring very important things
        self::$goldenKey = $settings[self::SETTINGS_GOLDEN_KEY] ?? json_decode(file_get_contents(__DIR__ . '/../config.json'), true)['key'];
        self::$runner = $this;

        if (!user::validateKey()) {
            throw new Exception("Invalid golden key!");
        }

        // Declaring output function
        if (isset($settings[self::SETTINGS_OUTPUTFUNCTION])) {
            if (!is_callable($settings[self::SETTINGS_OUTPUTFUNCTION])) throw new Exception("Output function is not callable!");

            $this->outputClosure = $settings[self::SETTINGS_OUTPUTFUNCTION];
        } else {
            $this->outputClosure = function ($message) {
                echo $message;
            };
        }

        //Making empty screen
        if (!isset($settings[self::SETTINGS_DO_NOT_CLEAR_CONSOLE]) || !$settings[self::SETTINGS_DO_NOT_CLEAR_CONSOLE]) {
            echo chr(27) . chr(91) . 'H' . chr(27) . chr(91) . 'J';
        }

        //Checking is needs set up
        if (!file_exists(__DIR__."/../config.json") and !isset($settings[self::SETTINGS_GOLDEN_KEY])) {
            run::$runner->output("Checking is bot configured...".PHP_EOL);
            require_once (__DIR__."/setup.php");
            setup::run();
        }

        //Creating folder for temp files
        if (!file_exists(__DIR__ . "/../temp")) {
            run::$runner->output("Creating temp folder..." . PHP_EOL);
            mkdir(__DIR__ . "/../temp");
        }

        //Folder for orders temp files
        if (!file_exists(__DIR__ . "/../temp/payments")) {
            run::$runner->output("Checking payments..." . PHP_EOL);
            mkdir(__DIR__ . "/../temp/payments");
            file_put_contents(__DIR__ . "/../temp/payments/IMPORTANT.README", "Files in this folder " .
                "are used to indent payment, and avoid duplicate event fire, i do not recommend to delete anything " .
                "here, to avoid double event fire");
        }

        $this->timers = new timer();
        $this->events = new events($this);
        $this->user = new user($settings);
        $this->message = new message($this);
        $this->users = array();

        if (isset($this->user->settings[self::SETTINGS_CUSTOM_LOOP]) && $this->user->settings[self::SETTINGS_CUSTOM_LOOP]) {
            $this->customLoop = true;
        }
    }

    /**
     * Gets user
     *
     * @param int $ID ID of user you want's got userRepository from
     * @return userRepository User repository
     */
    public function getUser(int $ID):userRepository {
        if (!isset($this->users[$ID])) {
            $return = new userRepository($ID);
            $this->users[$ID] = $return;
        } else {
            $return = $this->users[$ID];
        }

        return $return;
    }

    /**
     * You need to execute this on bot start
     *
     * This function does this:
     * - Starts main loop(if custom loop is not enabled)
     * - Adds repeated funcions that rises lots
     *
     * Warning: **Code after this function will not be executed**
     * @return void
     */
    public function run():void {
        if (isset($this->user->settings[self::SETTINGS_DO_NOT_CLEAR_CONSOLE])) {
            if (!$this->user->settings[self::SETTINGS_DO_NOT_CLEAR_CONSOLE]) {
                echo chr(27) . chr(91) . 'H' . chr(27) . chr(91) . 'J';
            } else {
                run::$runner->output("Console clean-up turned off!" . PHP_EOL);
            }
        } else {
            echo chr(27) . chr(91) . 'H' . chr(27) . chr(91) . 'J';
        }

        run::$runner->output("Ready!" . PHP_EOL);

        $this->timers->addRepeated(3, function () {
            $this->loop();
        }, false);

        if (!isset($this->user->settings[self::SETTINGS_DISABLE_LOT_RISE]) || !$this->user->settings[self::SETTINGS_DISABLE_LOT_RISE]) {
            $this->user->rise();
            $this->timers->addRepeated(300, function () {
                $this->user->rise();
            });
        }

        $this->isReady = true;
        $this->events->fireEvent(event::ready);

        while (!$this->customLoop) {
            $this->timers->loop();
            sleep(1);
        }
    }

    /**
     * Main loop function
     *
     * This function does this:
     * - Checks for new messages
     * - Checks for new payments
     * - Fires loop event
     *
     * @return void
     */
    private function loop():void {
        $this->events->fireEvent(event::loop);

        if (!isset($this->user->settings[self::SETTINGS_DISABLE_MESSAGE_CHECK]) || !$this->user->settings[self::SETTINGS_DISABLE_MESSAGE_CHECK]) {
            $msg = @$this->message->checkForMsg();
            if ($msg && $msg->author->answered) {
                if ($msg->author->ID != $this->user->ID) {
                    $msg->author->answered = false;
                    $this->events->fireEvent(event::message, $msg);
                    $msg->author->answered = true;
                }
            }
        }

        if ($this->user->checkForOrders()) {
            $payment = paymentRepository::new();

            if ($payment) {
                $this->events->fireEvent(event::payment, $payment);
            }
        }
    }

    /**
     * This function is used to output messages, you can change outputClosure property to change output function
     * You can also make outputClosure totalyClean, to avoid any output
     * Bot setup ignore this
     * Use setting SETTINGS_DO_NOT_CLEAR_CONSOLE to disable console clean-up
     *
     * TIP: This works only after READY event,
     * use setting SETTINGS_OUTPUTFUNCTION to change output function instantly after bot start
     *
     * @param string $message Message to output
     */
    public function output(string $message) {
        call_user_func(run::$runner->outputClosure, $message);
    }
}
