<?php

namespace App\Http\Controllers;

use App\Group;
use App\Setting;
use App\Traits\ContentStatus;
use App\Services\ExportService;
use Auth;
use Carbon\Carbon;
use Gate;
use Illuminate\Http\Request;
use Image;
use Storage;

class GroupController extends Controller
{
    public function __construct()
    {
        $this->middleware('verified', ['only' => ['create', 'store', 'edit', 'update', 'destroy']]);
        $this->middleware('auth', ['only' => ['indexOfMyGroups']]);
    }

    public function index(Request $request)
    {
        $groups = new Group();
        $groups = $groups->notSecret();

        $groups = $groups->with('tags', 'users', 'actions', 'discussions')
            ->orderBy('status', 'desc')
            ->orderBy('updated_at', 'desc');

        if (Auth::check()) {
            $groups = $groups->with('membership');
        }

        if ($request->has('search')) {
            $groups = $groups->search($request->get('search'));
        }

        $groups = $groups->paginate(20)->appends(request()->query());

        return view('dashboard.groups')
            ->with('tab', 'groups')
            ->with('groups', $groups);
    }

    public function indexOfMyGroups(Request $request)
    {
        $groups = $request->user()->groups();

        $groups = $groups->with('tags');

        if (Auth::check()) {
            $groups = $groups->with('membership');
        }

        if ($request->has('search')) {
            $groups = $groups->search($request->get('search'));
        }

        $groups = $groups->simplePaginate(20)->appends(request()->query());

        return view('dashboard.mygroups')
            ->with('tab', 'groups')
            ->with('groups', $groups);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show(Group $group)
    {
        $this->authorize('view', $group);

        $discussions = false;
        $actions = false;
        $files = false;
        $activities = false;
        $group_inbox = false;

        // User is logged
        if (Auth::check()) {
            if (Gate::allows('viewDiscussions', $group)) {
                $discussions = $group->discussions()
                    ->has('user')
                    ->with('user', 'group', 'userReadDiscussion', 'tags')
                    ->where('status', '>=', ContentStatus::NORMAL)
                    ->orderBy('status', 'desc')
                    ->orderBy('updated_at', 'desc')
                    ->limit(5)
                    ->get();
            }

            if (Gate::allows('viewFiles', $group)) {
                $files = $group->files()
                    ->with('user', 'tags', 'group')
                    ->where('status', '>=', ContentStatus::NORMAL)
                    ->orderBy('status', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
            }

            if (Gate::allows('viewActions', $group)) {
                $actions = $group->actions()
                    ->with('user', 'tags', 'group')
                    ->where('stop', '>=', Carbon::now()->subDay())
                    ->orderBy('start', 'asc')
                    ->limit(10)
                    ->get();
            }


            if (Auth::user()->isMemberOf($group) && $group->inbox()) {
                $group_inbox = $group->inbox();
            }
        } else { // anonymous user
            if ($group->isSecret()) {
                abort('404', 'No query results for model [App\Group].');
            }
            if ($group->isOpen()) {
                $discussions = $group->discussions()
                    ->has('user')
                    ->with('user', 'group', 'tags')
                    ->withCount('comments')
                    ->orderBy('updated_at', 'desc')
                    ->limit(5)
                    ->get();

                $files = $group->files()
                    ->with('user', 'tags', 'group')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();

                $actions = $group->actions()
                    ->with('user', 'tags', 'group')
                    ->where('start', '>=', Carbon::now())
                    ->orderBy('start', 'asc')
                    ->limit(10)
                    ->get();
            }
        }

        return view('groups.show')
            ->with('title', $group->name)
            ->with('group', $group)
            ->with('discussions', $discussions)
            ->with('actions', $actions)
            ->with('files', $files)
            ->with('admins', $group->admins()->get())
            ->with('group_inbox', $group_inbox)
            ->with('tab', 'home');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $this->authorize('create', Group::class);
        $group = new Group;

        return view('groups.create')
            ->with('group', $group)
            ->with('model', $group)
            ->with('allowedTags', $group->getAllowedTags())
            ->with('newTagsAllowed', $group->areNewTagsAllowed())
            ->with('title', trans('group.create_group_title'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', Group::class);

        $group = new group();

        $group->name = $request->input('name');
        $group->body = $request->input('body');

        if ($request->user()->isAdmin()) {
            $group->status = $request->input('status');
        }

        // handle group type
        if ($request->input('group_type') == \App\Group::SECRET) {
            if (setting('users_can_create_secret_group') || $request->user()->isAdmin()) {
                $group->group_type = $request->input('group_type');
            } else {
                abort(401, 'Cant create secret group on this instance, sorry');
            }
        } else {
            $group->group_type = $request->input('group_type');
        }

        if ($request->has('location')) {
            // Validate input
            try {
                $group->location = $request->input('location');
                } catch (\Exception $e) {
                return redirect()->route('groups.create', $group)
                 ->withErrors($e->getMessage() . '. Invalid location')
                 ->withInput();
            }
            // Geocode
            if (!$group->geocode()) {
                flash(trans('messages.location_cannot_be_geocoded'));
            } else {
                flash(trans('messages.ressource_geocoded_successfully'));
            }
        }

        if ($group->isInvalid()) {
            // Oops.
            return redirect()->route('groups.create')
                ->withErrors($group->getErrors())
                ->withInput();
        }

        $group->user()->associate(Auth::user());


        $group->setSetting('show_members', true);
        $group->setSetting('show_discussions', true);
        $group->setSetting('show_actions', true);
        $group->setSetting('show_files', true);
        $group->setSetting('show_comments', true);
        $group->setSetting('show_participants', true);
        $group->setSetting('show_cover', true);

        $group->save();


        if ($request->get('tags')) {
            $group->tag($request->get('tags'));
        } else {
            $group->detag();
        }

        // handle allowed tags
        $allowed_tags = explode(',', $request->get('allowed_tags'));
        $allowed_tags = array_map('trim', $allowed_tags);
        array_filter($allowed_tags, function ($value) {
            return $value !== '';
        });
        $group->setSetting('allowed_tags', $allowed_tags);

        // handle cover
        if ($request->hasFile('cover')) {
            $group->setCoverFromRequest($request);
        }


        // make the current user an admin of the group
        $membership = \App\Membership::firstOrNew(['user_id' => Auth::user()->id, 'group_id' => $group->id]);
        $membership->notification_interval = 60 * 24; // default to daily interval
        $membership->membership = \App\Membership::ADMIN;
        $membership->config = json_encode([]);
        $membership->save();

        // notify admins (if they want it)
        if (setting('notify_admins_on_group_create')) {
            foreach (\App\User::admins()->get() as $admin) {
                $admin->notify(new \App\Notifications\GroupCreated($group));
            }
        }

        flash(trans('messages.ressource_created_successfully'));

        return redirect()->action('GroupMembershipController@update', [$group]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit(Request $request, Group $group)
    {
        $this->authorize('update', $group);

        return view('groups.edit')
            ->with('group', $group)
            ->with('model', $group)
            ->with('allowedTags', $group->getAllowedTags())
            ->with('newTagsAllowed', $group->areNewTagsAllowed())
            ->with('selectedTags', $group->getSelectedTags())
            ->with('tab', 'admin');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function update(Request $request, Group $group)
    {
        $this->authorize('update', $group);

        $group->name = $request->input('name');
        $group->body = $request->input('body');

        if (Gate::allows('changeGroupStatus', $group)) {
            $group->status = $request->input('status');
        }

        if (Gate::allows('changeGroupType', $group)) {
            // handle secret group type
            if ($request->input('group_type') == \App\Group::SECRET) {
                if (setting('users_can_create_secret_group') || $request->user()->isAdmin()) {
                    $group->group_type = $request->input('group_type');
                } else {
                    abort(401, 'Can\'t create secret group on this instance, sorry');
                }
            } else {
                $group->group_type = $request->input('group_type');
            }
        }

        if ($request->has('location')) {
            $old_location = $group->location;
            // Validate input
            try {
                $group->location = $request->input('location');
                } catch (\Exception $e) {
                return redirect()->route('groups.create', $group)
                 ->withErrors($e->getMessage() . '. Invalid location')
                 ->withInput();
            }
            if ($group->location <> $old_location) {
              // Try to geocode
              if (!$group->geocode()) {
                  flash(trans('messages.location_cannot_be_geocoded'));
              } else {
                  flash(trans('messages.ressource_geocoded_successfully'));
              }
           }
        }

        $group->user()->associate(Auth::user());

        if ($request->get('tags')) {
            $group->retag($request->get('tags'));
        } else {
            $group->detag();
        }

        // handle allowed tags
        $allowed_tags = explode(',', $request->get('allowed_tags'));
        $allowed_tags = array_map('trim', $allowed_tags);
        array_filter($allowed_tags, function ($value) {
            return $value !== '';
        });
        $group->setSetting('allowed_tags', $allowed_tags);

        // handle navbar pinning
        if (Auth::user()->isAdmin()) {
            if ($request->has('pinned_navbar')) {
                $group->setSetting('pinned_navbar', true);
            } else {
                if ($group->getSetting('pinned_navbar')) {
                    $group->setSetting('pinned_navbar', false);
                }
            }
        }


        // validation
        if ($group->isInvalid()) {
            // Oops.
            return redirect()->route('groups.edit', $group)
                ->withErrors($group->getErrors())
                ->withInput();
        }

        // handle cover
        if ($request->hasFile('cover')) {
            $group->setCoverFromRequest($request);
        }

        $group->save();

        flash(trans('messages.ressource_updated_successfully'));

        return redirect()->route('groups.show', [$group]);
    }

    public function destroyConfirm(Request $request, Group $group)
    {
        $this->authorize('delete', $group);

        return view('groups.delete')
            ->with('group', $group)
            ->with('tab', 'home');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Group $group)
    {
        $this->authorize('delete', $group);
        $group->delete();
        flash(trans('messages.ressource_deleted_successfully'));

        return redirect()->action('DashboardController@index');
    }

    /**
     * Show the revision history of the group.
     */
    public function history(Group $group)
    {
        $this->authorize('history', $group);

        return view('groups.history')
            ->with('group', $group)
            ->with('tab', 'home');
    }

    /**
     * Export the group data.
     */
    public function export(Group $group)
    {
        $this->authorize('export', $group);
        $exportservice = new ExportService();
        $zipfile = $exportservice->export($group);

        if ($zipfile) {
            $name = "archive-group" . $group->id . "-" . Carbon::now()->format('Y-m-d_H-i-s') . ".zip";
            $headers = ['Content-Type' => 'application/zip', 'Content-Disposition' => 'attachment; filename="' . $name . '"'];
            return Storage::disk('tmp')->download($zipfile, $name, $headers);
        } else {
            abort(404, 'Export failed!');
        }
    }
}
