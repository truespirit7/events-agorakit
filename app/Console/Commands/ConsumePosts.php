<?php

namespace App\Console\Commands;

use App\Action;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
                    
                    // Если есть фото, скачиваем первое и устанавливаем как cover
                    if (!empty($data['photos']) && is_array($data['photos'])) {
                        $this->downloadAndSetCover($action, $data['photos'][0]);
                    }
                    
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

    /**
     * Скачивает фото по URL и устанавливает как cover для action
     */
    private function downloadAndSetCover(Action $action, string $photoUrl)
    {
        try {
            $this->info("Downloading cover from: " . $photoUrl);
            
            // Скачиваем фото
            $imageContent = file_get_contents($photoUrl);
            if ($imageContent === false) {
                $this->error("Failed to download image from: " . $photoUrl);
                return;
            }

            // Создаем директорию
            $coverPath = $action->getCoverPath();
            Storage::makeDirectory($coverPath);
            
            // Сохраняем оригинальное фото
            $coverFilePath = $coverPath . 'cover.jpg';
            Storage::put($coverFilePath, $imageContent);
            
            // Генерируем thumbnails
            $action->generateThumbnails();
            
            $this->info("Cover set successfully for action: " . $action->id);
            
        } catch (\Exception $e) {
            $this->error("Error setting cover for action " . $action->id . ": " . $e->getMessage());
        }
    }
}
