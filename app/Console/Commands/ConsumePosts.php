<?php

namespace App\Console\Commands;

use App\Action;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\DB;

use Illuminate\Console\Command;

class ConsumePosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:consume-posts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Получает uiid постов из RabbitMQ и сохраняет их в Actions';

    /**
     * Execute the console command.
     */

    public function handle()
    {
        $this->info('Starting to consume posts from RabbitMQ...');
        $connection = new AMQPStreamConnection(
            'host.docker.internal',
            5672,
            'admin',
            'securepassword'

        );
        $channel = $connection->channel();

        $channel->queue_declare('vk_posts_to_laravel', false, true, false, false);

        $callback = function ($msg) {
            $data = json_decode($msg->body, true);
            $user = DB::table('users')->where('admin', true)->first();
            $group = DB::table('groups')->where('community_id', $data['community_id'])->first();
            // Проверяем, нет ли уже такого поста
            if (!Action::where('events_uuid', $data['events_uuid'])->exists()) {

                // TODO - писать в start и end даты, если упоминаются в тексте
                // TODO -  neme - LLM - дай название мероприятию, основываясь на тексте
                // TODO - cover - брать картинку из вк, если есть

                // Action::create([
                //     'events_uuid' => $data['events_uuid'],
                //     'group_id' => $group->id,
                //     'user_id' => $user->id,
                //     'body' => $data['text'],
                //     'latitude' => $group->latitude,
                //     'longitude' => $group->longitude,
                //     'visibility' => 1,
                //     "cover" => "",
                // ]);

                new Action([
                    'name' => $data['name'],
                    'events_uuid' => $data['events_uuid'],
                    'group_id' => $group->id,
                    'user_id' => $user->id,
                    'body' => $data['text'],
                    "location"=>"Новосибирск",
                    'latitude' => $group->latitude,
                    'longitude' => $group->longitude,
                    'start' => now(),
                    'stop' => now()->addDays(7), // TODO: Определить дату окончания
                    'visibility' => 1,
                    "cover" => "",
                ])->save();
                $this->info("Added new event: " . $data['post_id']);
            } else {
                $this->warn("Duplicate post: " . $data['post_id']);
            }

            $msg->ack();  # Подтверждаем обработку
        };

        $channel->basic_consume(
            'vk_posts_to_send',
            '',
            false,
            false,
            false,
            false,
            $callback
        );

        $this->info('Waiting for messages...');
        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }
}
