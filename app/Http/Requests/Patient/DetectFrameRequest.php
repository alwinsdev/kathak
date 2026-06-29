<?php

declare(strict_types=1);

namespace App\Http\Requests\Patient;

use App\Models\PracticeSession;
use Illuminate\Foundation\Http\FormRequest;

class DetectFrameRequest extends FormRequest
{
    /**
     * Only the owning patient may submit frames for a session.
     */
    public function authorize(): bool
    {
        $session = $this->route('session');

        return $session instanceof PracticeSession && $this->user()->can('update', $session);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'image' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/png',
                'max:'.(int) config('practice.max_image_kb'),
            ],
        ];
    }
}
