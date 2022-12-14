<?php

namespace App\Http\Controllers;

use App\Exceptions\PageNotFound;
use App\Http\Services\CamAlarmFilesFilters;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Http\JsonResponse;
use SebastianBergmann\Diff\Exception;

class FilesServer extends Controller
{

    public function allCamFiles(Request $request): JsonResponse
    {
        $ftpDir = storage_path(env('PICS'));
        $camIdsList = File::directories($ftpDir);
        $date = Carbon::now()->format('Ymd');
        $dirFiles = [];
        $dirFiles['files'] = [];
        foreach ($camIdsList as $dir) {
            $filesPath = $dir . '/today';
            $megaCamFilesPath = $filesPath . '/' . $date . '/images';
            $basename = File::basename($dir);
            if (!File::exists($filesPath)) {
                File::makeDirectory($filesPath);
            }
            if (File::exists($megaCamFilesPath))
                $modified = time() - File::lastModified($megaCamFilesPath);
            else
                $modified = time() - File::lastModified($filesPath);
            $dirFiles['files_count'][$basename] = count(File::allFiles($filesPath));
            $dirFiles['dirs'][$basename] = count($this->getDirList($dir));
            $dirFiles['changed'][$basename] = $modified;
            $dirFiles['size'][$basename] = 0;
        }
        $tableStats = [];

        return response()->json(['dirFiles' => $dirFiles, 'ftpDir' => $ftpDir, 'tableStats' => $tableStats]);
    }

    protected function showFiles($filesPath, Request $request)
    {
        $page_size = $request->get('limit', 10000);
        $page = $request->get('page', 0);
        $camFiles = new CamAlarmFilesFilters;
        $title = '  Show Folder ' . $request->get('folder', null)
            . '  and subfolder '
            . $request->get('subfolder', null) . ' ';
        $pictures = $camFiles->sortFiles($filesPath, $page_size, $page, [
            'query' => $request->toArray(),
            'path' => '/' . $request->path(),
        ]);
        return response()->json(['pictures' => $pictures, 'title' => $title]);
    }


    public function getTotalStats()
    {
        $ftpDir = storage_path('ftp');
        $dirList = File::directories($ftpDir);
        $result = collect();
        foreach ($dirList as $cam) {
            $filesPath = $cam;
            $directories = File::directories($filesPath);
            $result->push([
                'camname' => basename($cam),
                'dirs' => count($directories),
                'size' => 0
            ]);
        }
        return response()->json([
            'data' => $result,
            'stats' => [
                'alldirs' => 0,
                'dirscount' => $result->sum('dirs')]
        ]);
    }


    public function allFilesInFolder($folder, Request $request)
    {
        $dirList = $this->getDirList($folder);
        $camAlarmFilesFilters = new CamAlarmFilesFilters();
        $sortFolders = $camAlarmFilesFilters->sortFolders($dirList, $request);
        $paginated = $camAlarmFilesFilters->add_folder_size($sortFolders, $request);
        $page_size = $request->get('limit', 10000);
        $page = $request->get('page', 0);
        if (! in_array($page_size, [500,10000])){
          $paginated = $camAlarmFilesFilters->paginate_folders($sortFolders, $page_size, $page, $request);
        }
        return response()->json(['result' => $paginated, 'folderName' => $folder]);
    }

    public function allFilesDetails(Request $request)
    {
        $query = $request->get('q', null);
        $camname = $request->get('folder', null);
        $filesPath = realpath(storage_path(env("PICS", "pics") . "/{$camname}"));
        $subfolder = $request->get('subfolder', null);
        if (!$query || (!File::exists($filesPath))) {
            throw new \Exception('please specify query');
        }

        try {
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
        if (!in_array(trim($total), [' ', '', '--total'])) return '0 KB';
        $io = exec('/usr/bin/du -sk' . $h . '  ' . $total . ' ' . $path);
        $sizes = explode("\t", $io);
        return $sizes[0];
    }

    /**
     * @param $folder
     * @return array
     */
    public function getDirList($folder): array
    {
        if (!File::exists($folder)) {
            $folder = storage_path(env("PICS", "pics") . "/$folder");
        }
        try {
            $dirList = File::directories($folder);
            foreach ($dirList as $k => $v) {
                if (preg_match("~today~i", $v)) {
                    unset($dirList[$k]);
                }
            }

        } catch (Exception $e) {
            $dirList = [];
        }

        return $dirList;
    }

}
