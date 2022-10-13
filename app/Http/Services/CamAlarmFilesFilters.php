<?php

namespace App\Http\Services;


use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
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


    public function sortFiles($dir, $pageSize = 10000, $page = null, $options = [])
    {
        return $this->paginate(collect(File::allFiles($dir))
            ->filter(function ($file) {
                return in_array($file->getExtension(), ['png', 'gif', 'jpg']);
            })
            ->sortBy(function ($file) {
                return $file->getMTime();
            })
            ->map(function ($file) use ($dir) {
                return [
                    'origPath' => $file->getBaseName(),
                    'imgpath' => env("REMOTE_HOST") . preg_replace('~^\/.+ftp\/~i', '', $file->getPath()) . '/' . $file->getBaseName(),
                    'path' => $file->getPath(),
                    'date' => Carbon::createFromTimestamp($file->getMTime()),
                    'realPathName' => $file->getRealPath()];
            }), $pageSize, $page, $options);
    }


    /**
     *
     * @param array|Collection $items
     * @param int $perPage
     * @param int $page
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


    public function sortFolders($filesPath, Request $request)
    {
        $folders = [];
        $start = $page_number = $request->get('page', 1);

        if ($page_number == 1) {
            $start = 0;
        }
        $page_size = $request->get('page_size', 30);
        $page_range = $start * $page_size + $page_size;
        $pages_range = range($start * $page_size, $page_range);
        foreach ($filesPath as $key => $foldePath) {
            if (in_array($key, $pages_range)) {
                $files = File::allFiles($foldePath);
            } else {
                $files = [];
            }
            $basename = class_basename($foldePath);
            if (!preg_match('~day-~i', $basename)) {
                $timeStamp = Carbon::parse($basename);
            } else {
                $folderName = str_replace('day-', '', class_basename($foldePath));
                $timeStamp = Carbon::parse($folderName);
            }
            $folders[$timeStamp->timestamp] = ['size' => count($files), 'date' => $timeStamp->format('d-m-Y'), 'origPath' => $foldePath, 'folder' => $basename];
        }
        krsort($folders);
        return $folders;
    }
}
