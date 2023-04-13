<?php

/**
 * Created by NiNaCoder.
 * Date: 2019-07-04
 * Time: 22:09
 */

namespace App\Http\Controllers\Frontend;

use App\Models\Artist;
use App\Models\Genre;
use App\Models\Mood;
use Illuminate\Support\Facades\Validator;
use View;
use Illuminate\Http\Request;
use Auth;
use App\Models\Upload;
use App\Models\Role;

class UploadController
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function index()
    {
        if (!Role::getValue('artist_allow_upload') || !auth()->user()->artist_id) {
            $view = View::make('artist-management.claim');

            if ($this->request->ajax()) {
                $sections = $view->renderSections();
                return $sections['content'];
            }

            return $view;
        }

        $artist = Artist::findOrFail(auth()->user()->artist_id);

        $allowGenres = Genre::where('discover', 1)->get();
        $allowMoods = Mood::all();

        $view = View::make('upload.index')
            ->with('artist', $artist)
            ->with('allowGenres', $allowGenres)
            ->with('allowMoods', $allowMoods);

        if ($this->request->ajax()) {
            $sections = $view->renderSections();
            return $sections['content'];
        }

        return $view;
    }

    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimetypes:application/octet-stream,audio/mp3,audio/x-m4a,audio/flac,audio/x-flac,audio/wav,audio/x-wav,video/x-ms-asf,audio/aac,audio/x-hx-aac-adts,audio/x-aiff,audio/ogg,audio/mpeg,audio/*|max:' .  intval(config('settings.max_audio_file_size')),
        ]);

        /** Check if user have permission to upload */
        if (! Role::getValue('artist_allow_upload') || ! auth()->user()->artist_id) {
            abortNoPermission();
        }

        if ($validator->validate()) {
            if (auth()->user()->artist_id) {
                $res = (new Upload())->handle($this->request, $artistIds = auth()->user()->artist_id);
            } else {
                $res = (new Upload())->handle($this->request);
            }

            return response()->json($res);
        }
    }
}
