<?php

namespace App\Http\Services;


use App\Http\Controllers\FilesServer;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CamAlarmFilesFilters
{

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
                    'path' => '',
                    'date' => Carbon::createFromTimestamp($file->getMTime()),
                    'realPathName' => ''
                ];
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
        foreach ($filesPath as $key => $foldePath) {

            $basename = class_basename($foldePath);
            if (!preg_match('~day-~i', $basename)) {
                $timeStamp = Carbon::parse($basename);
            } else {
                $folderName = str_replace('day-', '', class_basename($foldePath));
                $timeStamp = Carbon::parse($folderName);
            }
            $folders[$timeStamp->timestamp] = ['size' => 0, 'qty' => 0, 'date' => $timeStamp->format('d-m-Y'), 'origPath' => $foldePath, 'folder' => $basename];
        }
        krsort($folders);
        return $folders;
    }

    /**
     * @param array $folder_list
     * @param Request $request
     * @return array
     */
    public function add_folder_size(array $folder_list, Request $request): array
    {
        $count = 0;
        $page_number = $request->get('page', 1);
        $page_size = $request->get('page_size', 10);
        array_walk($folder_list, function (&$v, $k) use (&$count, $request, $page_number, $page_size) {
            $start =$page_number;
            if ($page_number == 1) {
                $start = 0;
            }
            $pages_range = range($start * $page_size, ($start * $page_size) + $page_size);
            if (in_array($count, $pages_range)) {
                $v['size'] = FilesServer::human_folderSize($v['origPath']);
                $v['qty'] = count(File::allFiles($v['origPath']));
            }
            $v['origPath'] = class_basename($v['origPath']);
            $count++;
        });
        return $folder_list;
    }
}


