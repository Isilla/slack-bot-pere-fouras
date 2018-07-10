<?php
require_once('vendor/autoload.php');

class bots {
    private $responseEnigme;
    private $enigmes;
    private $nopeResponses;

    public function __construct()
    {
        $this->responseEnigme = [];

        $yml  = \Symfony\Component\Yaml\Yaml::parseFile('enigmes.yml');
        $this->enigmes = $yml['enigmes'];
        $this->nopeResponses = $yml['nope'];
    }

    public function process(){
        $loop = React\EventLoop\Factory::create();

        $client = new Slack\RealTimeClient($loop);
        $client->setToken('key');
        $client->connect();

        $client->on('message', function ($data) use ($client) {

            $client->getChannelGroupOrDMByID($data['channel'])->then(function ($channel) use ($client, $data) {
                /** @var $channel \Slack\DirectMessageChannel */
                $channelId = $channel->data['id'];

                if(!isset($this->responseEnigme[$channelId]))
                    $this->responseEnigme[$channelId] = null;

                echo (new DateTime())->format('Y-m-d h:i:s') . "Incoming message: ".$data['text']." | Channel : " . $channelId . " | Réponse énigme : ".$this->responseEnigme[$channelId]."\n";

                $message = null;

                if($this->responseEnigme[$channelId] != null) {
                    if(preg_match('/(.*)(' . $this->responseEnigme[$channelId] . ')(.*)/', strtolower($data['text']))) {
                        $message = $client->getMessageBuilder()
                            ->setText('Bravo ! Voici la clé :key:')
                            ->setChannel($channel)
                            ->create();
                        $this->responseEnigme[$channelId] = null;
                    } else {
                        $numberNope = rand(0, count($this->nopeResponses)-1);
                        $message = $client->getMessageBuilder()
                            ->setText($this->nopeResponses[$numberNope])
                            ->setChannel($channel)
                            ->create();
                    }
                }


                if(strtolower($data['text']) == 'énigme') {
                    $number = rand(0, count($this->enigmes)-1);
                    $message = $client->getMessageBuilder()
                        ->setText('Voici l\'énigme : 
' . $this->enigmes[$number]['enigme'])
                        ->setChannel($channel)
                        ->create();
                    $this->responseEnigme[$channelId] = $this->enigmes[$number]['reponse'];
                }


                if($message === null) {
                    $message = $client->getMessageBuilder()
                        ->setText('Bonjour visiteur, si tu veux jouer, saisie `énigme`')
                        ->setChannel($channel)
                        ->create();
                }


                $client->postMessage($message);
            });

        });

        $loop->run();
    }
}

$obj = new bots();
echo $obj->process();

