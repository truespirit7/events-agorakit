<?php

use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

use App\Setting;
use App\Participation;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $faker = Faker::create();

        $faker->addProvider(new \Mmo\Faker\PicsumProvider($faker));

        // set intro text
        setting()->set('homepage_presentation', $this->richtext());
        setting()->set('homepage_presentation_for_members', $this->richtext());
        setting()->set('help_text', $this->richtext());



        // create users
        DB::table('users')->delete();

        // first created user is automagically admin
        $admin = App\User::create([
            'email'    => 'admin@agorakit.org',
            'password' => bcrypt('123456789'),
            'body'     => $faker->text,
            'name'     => 'admin',
            'verified' => 1,
        ]);

        // add avatar to admin user
        Storage::disk('local')->makeDirectory('users/' . $admin->id);
        try {
            Image::read($faker->picsumUrl(500, 400))->widen(500)->save(storage_path() . '/app/users/' . $admin->id . '/cover.jpg')->fit(128, 128)->save(storage_path() . '/app/users/' . $admin->id . '/thumbnail.jpg');
        } catch (Exception $e) {
        }

        // a second normal user
        $normal_user = App\User::create([
            'email'    => 'newbie@agorakit.org',
            'password' => bcrypt('123456789'),
            'body'     => $faker->text,
            'name'     => 'newbie',
            'verified' => 1,
        ]);

        for ($i = 1; $i <= 50; $i++) {
            $user = App\User::create([
                'email'    => $faker->safeEmail,
                'password' => bcrypt('secret'),
                'name'     => $faker->name,
                'body'     => $faker->text(1000),
            ]);

            // add avatar to every user

            // Storage::disk('local')->makeDirectory('users/' . $user->id);
            //     Image::read(file_get_contents('https://picsum.photos/500/400'))->save(storage_path() . '/app/users/' . $user->id . '/cover.jpg')->cover(128, 128)->save(storage_path() . '/app/users/' . $user->id . '/thumbnail.jpg');
        }

        // create 10 groups
        for ($i = 1; $i <= 10; $i++) {
            $group = App\Group::create([
                'name' => $faker->city . '\'s user group',
                'body' => $faker->text,
                'user_id' => $admin->id,
                'location' => $faker->city,
                'latitude' => $faker->latitude,
                'longitude' => $faker->longitude,
                'settings' => json_encode([
                    'show_members' => true,
                    'show_discussions' => true,
                    'show_actions' => true,
                    'show_files' => true,
                    'show_comments' => true,
                    'show_participants' => true,
                    'show_cover' => true,
                ]),
            ]);

            $group->group_type = rand(0, 2);

            $group->save();

            $group->tag($this->tags());

            // add cover image to groups
            // Storage::disk('local')->makeDirectory('groups/' . $group->id);
            // Image::read(file_get_contents('https://picsum.photos/800/600'))->save(storage_path() . '/app/groups/' . $group->id . '/cover.jpg')->cover(300, 200)->save(storage_path() . '/app/groups/' . $group->id . '/thumbnail.jpg');

            // add members to the group
            for ($j = 1; $j <= $faker->numberBetween(5, 20); $j++) {
                $membership = \App\Membership::firstOrNew(['user_id' => App\User::orderByRaw('RANDOM()')->first()->id, 'group_id' => $group->id, 'config' => json_encode([])]);
                $membership->membership = \App\Membership::MEMBER;
                $membership->notification_interval = 600;

                // we prented the user has been already notified once, now. The first mail sent will be at the choosen interval from now on.
                $membership->notified_at = Carbon::now();
                $membership->save();
            }

            // add discussions to each group
            for ($k = 1; $k <= $faker->numberBetween(5, 20); $k++) {
                $discussion = App\Discussion::create([
                    'name' => $faker->city,
                    'body' => $faker->text,
                ]);
                // attach one random author & group to each discussion
                $discussion->user_id = App\User::orderByRaw('RANDOM()')->first()->id;
                $discussion->group_id = App\Group::orderByRaw('RANDOM()')->first()->id;
                $discussion->save();

                $discussion->tag($this->tags());

                // Add comments to each discussion

                for ($l = 1; $l <= $faker->numberBetween(5, 20); $l++) {
                    $comment = new \App\Comment();
                    $comment->body = $faker->text;
                    $comment->user_id = App\User::orderByRaw('RANDOM()')->first()->id;
                    $discussion->comments()->save($comment);
                }
            }

            // add actions to each group
            for ($m = 0; $m <= $faker->numberBetween(5, 20); $m++) {
                $start = $faker->dateTimeThisMonth('+2 months');
                $action = App\Action::create([
                    'name' => $faker->sentence(5),
                    'body' => $faker->text,
                    'start'    => $start,
                    'stop'     => Carbon::instance($start)->addHour(),
                    'location' => $faker->city,
                ]);
                // attach one random author & group to each action
                $action->user_id = App\User::orderByRaw('RANDOM()')->first()->id;
                $action->group_id = App\Group::orderByRaw('RANDOM()')->first()->id;
                if ($action->isInvalid()) {
                    dd($action->getErrors());
                }
                $action->save();

                // add a cover image to action
                Storage::disk('local')->makeDirectory('groups/' . $action->group->id . '/actions/' . $action->id);
                Image::read(file_get_contents('https://picsum.photos/800/600'))->save(storage_path() . '/app/groups/' . $action->group->id . '/actions/' . $action->id . '/cover.jpg');

                $action->tag($this->tags());

                for ($pp = 1; $pp <= $faker->numberBetween(1, 20); $pp++) {
                    // add participants to each action
                    $rsvp = Participation::firstOrCreate([
                        'user_id' => App\User::orderByRaw('RANDOM()')->first()->id,
                        'action_id' => $action->id
                    ]);

                    $status = $faker->numberBetween(1, 3);
                    if ($status == 1) {
                        $rsvp->status = Participation::PARTICIPATE;
                    } elseif ($status == 2) {
                        $rsvp->status = Participation::WONT_PARTICIPATE;
                    } elseif ($status == 3) {
                        $rsvp->status = Participation::UNDECIDED;
                    }

                    $rsvp->save();
                }
            }

            // add files to each group
            for ($n = 1; $n <= $faker->numberBetween(5, 20); $n++) {
                $start = $faker->dateTimeThisMonth('+2 months');
                $file = App\File::create([
                    'name' => $faker->sentence(5),
                    'path'    => $faker->url,
                    'item_type' => 2
                ]);
                // attach one random author & group to each action
                $file->user_id = App\User::orderByRaw('RANDOM()')->first()->id;
                $file->group_id = App\Group::orderByRaw('RANDOM()')->first()->id;
                if ($file->isInvalid()) {
                    dd($file->getErrors());
                }
                $file->save();

                $file->tag($this->tags());
            }
        }
    }

    public function tags()
    {
        $amount = rand(0, 10);
        $tags = array();

        $faker = Faker::create();
        for ($i = 0; $i < $amount; $i++) {
            $tags[] = $faker->word;
        }


        return implode(",", $tags);
    }


    public function richtext()
    {
        $amount = rand(3, 10);

        $text = '';

        $faker = Faker::create();
        for ($i = 0; $i < $amount; $i++) {
            $text .= '<h2>' . $faker->sentence . '</h2>';
            $text .= implode("<p>", $faker->paragraphs(rand(1, 4)));
        }

        return $text;
    }
}
