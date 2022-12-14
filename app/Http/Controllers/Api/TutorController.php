<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

use App\Libs\Response;

use App\Http\Repositories\Finder\PersonFinder;
use App\Http\Repositories\Tutor;
use App\Http\Services\TutorService;

class TutorController extends Controller
{
    public function index(Request $request)
    {
        $finder = new PersonFinder();
        $finder->setAccessControl($this->getAccessControl());
        $finder->setCategory('tutor');

        if (isset($request->order_by))
            $finder->setOrderBy($request->order_by);

        if (isset($request->order_type))
            $finder->setOrderType($request->order_type);

        if (isset($request->paginate)) {
            $finder->usePagination($request->paginate);

            if (isset($request->page))
                $finder->setPage($request->page);

            if (isset($request->per_page))
                $finder->setPerPage($request->per_page);
        }

        $paginator = $finder->get();
        $data = [];

        if (!$finder->isUsePagination()) {
            foreach ($paginator as $item) {
                $data[] = $item;
            }

        } else {
            foreach ($paginator->items() as $item) {
                $data[] = $item;
            }

            foreach ($paginator->toArray() as $key => $value)
                if ($key != 'data')
                    $data['pagination'][$key] = $value;
        }

        $response = new Response;
        return $response->json($data, "success get tutor data");

    }

    public function publicIndex(Request $request)
    {
        $finder = new PersonFinder();
        $finder->setCategory('tutor');

        if (isset($request->order_by))
            $finder->setOrderBy($request->order_by);

        if (isset($request->order_type))
            $finder->setOrderType($request->order_type);

        if (isset($request->paginate)) {
            $finder->usePagination($request->paginate);

            if (isset($request->page))
                $finder->setPage($request->page);

            if (isset($request->per_page))
                $finder->setPerPage($request->per_page);
        }

        $paginator = $finder->get();
        $data = [];

        if (!$finder->isUsePagination()) {
            foreach ($paginator as $item) {
                $data[] = $item;
            }

        } else {
            foreach ($paginator->items() as $item) {
                $data[] = $item;
            }

            foreach ($paginator->toArray() as $key => $value)
                if ($key != 'data')
                    $data['pagination'][$key] = $value;
        }

        $response = new Response;
        return $response->json($data, "success get tutor data");

    }


    public function upsert(Request $request)
    {
        $response = new Response();
        $fields = [];
        $this->filterByAccessControl('tutor-create');

        // decode file
        $file = $request->file('file');
        $fields = json_decode($request->data, true);
        if ($file) {
            $fields['file'] = $file;
        } else {
            $fields['file'] = null;
        }

        dd($fields);

        $rules = [
            'id' => 'required|uuid',
            'name' => 'required|string',
            'city_id' => ['required', 'uuid',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->where('group_by', 'cities');
                }),
            ],
            'address' => 'required|string',
            'phone' => [
                'required',
                'string',
                'min:4',
                Rule::unique('people', 'phone')->ignore($fields['id'] ?? Str::uuid()->toString()),
            ],
            'email' => [
                'required',
                'email',
                Rule::unique('people', 'email')->ignore($fields['id'] ?? Str::uuid()->toString()),
            ],
            'bio' => 'required|string',
            'social_medias' => 'nullable|array',
            'course_ids' => ['required', 'array', 'min:1'],
            'course_ids.*' => [
                'uuid',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->where('group_by', 'courses');
                }),
            ],
            'course_level_ids' =>  ['required', 'array','min:1'],
            'course_level_ids.*' => [
                'uuid',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->where('group_by', 'course_levels');
                }),
            ],
            'schedules' => 'required|array|min:1',
            'fee' => 'required|numeric',
            'file' => ['nullable', 'file', 'mimes:jpeg,jpg,png', 'max:1048'],
        ];

        $validator = Validator::make($fields, $rules);

        if ($validator->fails())
            return $response->json(null, $validator->errors(), HttpResponse::HTTP_UNPROCESSABLE_ENTITY);

        $repository = new Tutor();
        $service = new TutorService($repository);
        $data = $service->upsert((object) $fields);

        return $response->json($data, 'ok');
    }

    public function show($id)
    {
        $response = new Response();
        $this->filterByAccessControl('tutor-read');

        $repository = new Tutor();
        $service = new TutorService($repository);
        $data = $service->find($id);

        return $response->json($data, 'success get tutor data');
    }

    public function publicShow($id)
    {
        $response = new Response();
        $repository = new Tutor();
        $service = new TutorService($repository);
        $data = $service->find($id);

        return $response->json($data, 'success get tutor data');
    }

    public function destroy($id)
    {
        //
    }
}
