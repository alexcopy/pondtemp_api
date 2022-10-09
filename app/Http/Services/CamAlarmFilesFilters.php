<?php

namespace App\Http\Services;


use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CamAlarmFilesFilters
{


    public static function fileNameIsKeyToSort(array $alarmFiles, $camType, $direction = 'desc')
    {
        $parsedFileName = [];
        foreach ($alarmFiles as $key => $file) {
            $alarmFile = $file->getPathName();
            $keys = explode('_', $alarmFile);
            if (($camType == 'koridor') || (count($keys) <= 1)) {
                $keys = preg_replace('~Koridor-20\d\d\-~i', '', $alarmFile);
                $keys = [str_replace('-', '', $alarmFile)];
            }
            $sortArg = self::getSortArgBasedOnCamType($camType);
            if (isset($keys[$sortArg])) {
                $parsedFileName[$keys[$sortArg]] = $file;
            }
        }

        if ($direction == 'desc') {
            krsort($parsedFileName, SORT_NUMERIC);
        } else {
            ksort($parsedFileName, SORT_NUMERIC);
        }
        return $parsedFileName;
    }

    protected static function getSortArgBasedOnCamType($camType)
    {
        if (in_array($camType, ['mamacam', 'pond'])) {
            return 2;
        } elseif (in_array($camType, [32177699, 34568373])) {
            return 1;
        } else {
            return 0;
        }

    }


    public function sortFiles($dir, $pageSize=10000, $page=null, $options=[])
    {
        return $this->paginate(collect(File::allFiles($dir))
            ->filter(function ($file) {
                return in_array($file->getExtension(), ['png', 'gif', 'jpg']);
            })
            ->sortBy(function ($file) {
                return $file->getMTime();
            })
            ->map(function ($file) {
                return [
                    'origPath'=>$file->getBaseName(),
                    'imgpath'=>env("REMOTE_HOST"),
                    'path'=>$file->getPath(),
                    'date'=>Carbon::createFromTimestamp($file->getMTime()),
                    'realPathName'=>$file->getRealPath()];
            }), $pageSize, $page, $options);
    }


    /**
     *
     * @param array|Collection      $items
     * @param int   $perPage
     * @param int  $page
     * @param array $options
     *
     * @return LengthAwarePaginator
     */
    public function paginate($items, $perPage = 15, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }


    public function sortFolders($filesPath)
    {

        $folders=[];
        foreach ($filesPath as $foldePath) {

            $files = File::allFiles($foldePath);
            $basename = class_basename($foldePath);
            if (!preg_match('~day-~i', $basename)) {
                $timeStamp = Carbon::parse($basename);
            }else{
            $folderName = str_replace('day-', '', class_basename($foldePath));
            $timeStamp = Carbon::parse($folderName);
            }
            $folders[$timeStamp->timestamp] = ['size'=>count($files), 'date' => $timeStamp->format('d-m-Y'), 'origPath' => $foldePath, 'folder' => $basename];
        }
        krsort($folders);
        return $folders;
    }
}
