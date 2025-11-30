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

        $channel->queue_declare('vk_posts_to_send', false, true, false, false);

        $channel->queue_bind('vk_posts_to_send', 'events_fanout');

        $callback = function ($msg) {
            $data = json_decode($msg->body, true);
            $this->info('Received message: ' . json_encode($data));
            $user = DB::table('users')->where('admin', true)->first();
            $group = DB::table('groups')->where('community_id', $data['community_id'])->first();
            if (!$group) {
                $this->error('Group not found for community_id: ' . $data['community_id']);
                $msg->ack();
                return;
            }
            // Проверяем, нет ли уже такого поста
            if (!Action::where('events_uuid', $data['events_uuid'])->exists()) {

                try {
                    $action = new Action([
                        'name' => $data['name'],
                        'events_uuid' => $data['events_uuid'],
                        'group_id' => $group->id,
                        'user_id' => $user->id,
                        'body' => $group->name . "\n\n" . $data['text'],
                        'location' => 'Новосибирск',
                        'latitude' => $group->latitude,
                        'longitude' => $group->longitude,
                        'start' => now(),
                        'stop' => now()->addDays(7),
                        'visibility' => 1,
                        'cover' => '',
                    ]);
                    $action->save();
                    $this->info("Added new event: " . $data['post_id']);
                } catch (\Exception $e) {
                    $this->error('Error saving action: ' . $e->getMessage());
                }
            } else {
                $this->warn("Duplicate post: " . $data['post_id']);
            }

            $msg->ack();
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
