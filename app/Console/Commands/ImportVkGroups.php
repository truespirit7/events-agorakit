<?php

namespace App\Console\Commands;

use App\Group;

use Illuminate\Console\Command;

class ImportVkGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:import-vk-groups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        echo "Starting import of VK groups...\n";
        $fromVK = $this->getVKGroupData();
        foreach ($fromVK as $group) {
            $this->createGroup($group);
        }
        echo "Import completed.\n";
    }

    public function getVKGroupData()
    {
        // table - vk_groups
        $data = \DB::connection('vk_parser')->table('vk_groups')->whereNotNull('community_id')->get();

        return $data;
    }
    function createGroup($data)
    {
        $group = new Group;
        // $group->id = $data->id;
        $group->name = $data->name; // TODO: Брать название из ВК
        $group->vk_link = $data->vk_link;
        $group->community_id = $data->community_id;

        $group->body = $data->description;
        // $group->cover = $data->cover;
        $group->color = 'blue'; // default color
        $group->group_type = 0; // default group type
        // admin
        $admin = \App\User::where('admin', true)->first();
        $group->user()->associate($admin);

        $group->location = json_encode([
            'address' => "",
            'city' => "Новосибирск",
            'country' => "Россия",
            'postal_code' => "",
        ]);

        $coordString = $data->coordinates;
        $coords = explode(',', trim($coordString));
        $group->latitude = $coords[1] ?? null;
        $group->longitude = $coords[0] ?? null;
        // $group->settings = (array) $data->settings;
        $group->setSetting('show_members', true);
        $group->setSetting('show_discussions', true);
        $group->setSetting('show_actions', true);
        $group->setSetting('show_files', true);
        $group->setSetting('show_comments', true);
        $group->setSetting('show_participants', true);
        $group->setSetting('show_cover', true);

        // regenerate a slug just in case it's already taken
        // $slug = SlugService::createSlug(Group::class, 'slug', $data->slug);

        // $group->slug = $slug;
        // $group->status = $data->status;

        // $user = $this->createUser($data->user);
        // $group->user()->associate($user);
        // $group->name = $group->name . ' (imported)';

        // if ($group->exists) {
        //     $this->error('A group with the same id exist on this install this is not a good idea to import again. Yes we need UUIDs to solve this issue');
        //     die();
        // }


        // if ($group->isValid()) {
        //     $group->save();
        //     return $group;
        // } else {
        //     $this->error($group->getErrors());
        //     return false;
        // }
    }
}
