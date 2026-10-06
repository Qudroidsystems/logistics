<?php

namespace App\Traits;

use App\Models\Studentpicture;
use App\Models\Staffpicture;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

trait ImageManager
{
    public function uploads($file, $path, $userid)
    {
        if ($file) {
            $fileName = $userid . '_' . time() . '.' . $file->getClientOriginalExtension();

            // Save to correct folder: staff_avatars (with underscore)
            Storage::disk('public')->put('staff_avatars/' . $fileName, File::get($file));

            // Update Staffpicture record
            Staffpicture::updateOrCreate(
                ['staffId' => $userid],
                ['picture' => $fileName]
            );

            // Optional: update user avatar column
            User::where('id', $userid)->update(['avatar' => $fileName]);

            return [
                'fileName' => $fileName,
                'fileType' => $file->getClientOriginalExtension(),
                'filePath' => 'staff_avatars/' . $fileName,
                'fileSize' => $this->fileSize($file)
            ];
        }
    }

    public function studentuploads($file, $path, $userid)
    {
        if ($file) {
            $fileName = $userid . '_' . time() . '.' . $file->getClientOriginalExtension();

            // Save to correct folder: student_avatars (with underscore)
            Storage::disk('public')->put('student_avatars/' . $fileName, File::get($file));

            // Update Studentpicture record
            Studentpicture::updateOrCreate(
                ['studentid' => $userid],
                ['picture' => $fileName]
            );

            User::where('id', $userid)->update(['avatar' => $fileName]);

            return [
                'fileName' => $fileName,
                'fileType' => $file->getClientOriginalExtension(),
                'filePath' => 'student_avatars/' . $fileName,
                'fileSize' => $this->fileSize($file)
            ];
        }
    }

    public function fileSize($file, $precision = 2)
    {
        $size = $file->getSize();
        if ($size > 0) {
            $base = log($size) / log(1024);
            $suffixes = [' bytes', ' KB', ' MB', ' GB', ' TB'];
            return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
        }
        return $size;
    }
}
