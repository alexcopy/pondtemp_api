<?php

namespace App\Http\Controllers;

use App\Exceptions\PageNotFound;
use App\Http\Services\CamAlarmFilesFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Http\JsonResponse;

class FilesServer extends Controller
{

    public function allCamFiles(Request $request): JsonResponse
    {

        $ftpDir = storage_path('ftp');
        $camIdsList = File::directories($ftpDir);

        $dirFiles = [];
        $dirFiles['files'] = [];

        foreach ($camIdsList as $dir) {
            $filesPath = $dir . '/today';
            $basename = File::basename($dir);
            if (!File::exists($filesPath)) {
                File::makeDirectory($filesPath);
            }
            $dirFiles['files'][$basename] = File::allFiles($filesPath);
            $dirFiles['dirs'][$basename] = File::directories($dir);
            $dirFiles['changed'][$basename] = File::lastModified($filesPath);
            $dirFiles['size'][$basename] = self::human_folderSize($filesPath);
        }
        $tableStats = [];

        return response()->json(['dirFiles' => $dirFiles, 'ftpDir' => $ftpDir, 'tableStats' => $tableStats]);
    }

    protected function showFiles($filesPath, Request $request)
    {
        $pagesize = 60;
        $page = $request->get('page', 0);
        $camFiles = new CamAlarmFilesFilters;
        $title = '  Show Folder ' . $request->get('folder', null)
            . '  and subfolder '
            . $request->get('subfolder', null) . ' ';
        $pictures = $camFiles->sortFiles($filesPath, $pagesize, $page, [
            'query' => $request->toArray(),
            'path' => '/' . $request->path(),
        ]);
        return view('pages.camssnapshots', compact(['pictures', 'title']));
    }

    protected function showFolders($folderPath, Request $request)
    {
        $pageSize = 10;
        $folderName = $request->get('folder', null);
        $page = $request->get('page', 0);
        $camFiles = new CamAlarmFilesFilters;
        $sortedFolders = $camFiles->sortFolders($folderPath);
        $result = $camFiles->paginate($sortedFolders, $pageSize, $page, [
            'query' => $request->toArray(),
            'path' => '/' . $request->path(),
        ]);
        return view('pages.deatails', compact(['result', 'folderName']));
    }

    public function allFilesDetails(Request $request)
    {
        $query = $request->get('q', null);
        $camname = $request->get('folder', null);
        $camera = Cameras::where('name', $camname)->get()->first();
        $folder = optional($camera)->realpath;
        $subfolder = $request->get('subfolder', null);
        if (!$query || (!$folder)) {
            throw new \Exception('please specify query');
        }

        try {
            $filesPath = storage_path('ftp/' . $folder);
            if ($query == 'showtoday') {
                return $this->showFiles($filesPath . '/today', $request);
            } elseif ($query == 'showfolders') {
                return $this->showFolders($filesPath, $request);
            } elseif ($query == 'showfolderfiles') {
                return $this->showFiles($filesPath . '/' . $subfolder, $request);
            }
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
        throw new \Exception('Something went very wrong');
    }

    public static function human_folderSize($path, $h = 'h', $total = ' ')
    {
// $total='--total'
        if (!in_array(trim($total), [' ', '', '--total'])) return '0 KB';
//        $io = exec('/usr/bin/du -sk' . $h . '  ' . $total . ' ' . $path);
//        $sizes = explode("\t", $io);
        return 0; //$sizes[0];
    }

    public static function getHumanFoldersSize(array $folders)
    {
        if (empty($folders)) return '0 kb';
        $path = implode('  ', $folders);
        return self::human_folderSize($path, 'h', ' --total');
    }


    /**
     * Handle the incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        //
    }
}
