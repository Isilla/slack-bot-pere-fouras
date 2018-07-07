<?php
require_once('vendor/autoload.php');

class bots {
    private $responseEnigme;
    private $enigmes;

    public function __construct()
    {
        $this->responseEnigme = [];
        $this->enigmes = [
            0 => [
                'enigme' => '
Echelon suprême à l\'Opéra
Jamais sous l\'eau elle ne fila.
Bonne lorsqu\'on y croit.
Même étreinte on l\'aperçoit.
Qui est-elle ?',
                'reponse' => 'étoile'
            ],
            1 => [
                'enigme' => '
Bien des vêtements y défilent,
Elle est propice aux coups de fil,
Parfois conçue pour piloter.
Sur le Fort, elle est abandonnée.
Qui est-elle ?',
                'reponse' => 'cabine'
            ],
        ];
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
                            ->setText('Bravo !
https://thumbs.gfycat.com/LastingSmallAnt-max-1mb.gif')
                            ->setChannel($channel)
                            ->create();
                        $this->responseEnigme[$channelId] = null;
                    } else {
                        $message = $client->getMessageBuilder()
                            ->setText('Nope !')
                            ->setChannel($channel)
                            ->create();
                    }
                }



                if(strtolower($data['text']) == 'enigme') {
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
                        ->setText('Si tu veux jouer, saisie `enigme`')
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

