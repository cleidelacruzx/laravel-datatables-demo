@extends('datatables.template')

@section('demo')
<div class="row">
    <div class="col-md-12">
        <table id="datatable" class="table table-condensed">
            <thead>
                <tr>
                    <th>Stars</th>
                    <th>Repo</th>
                    <th>Owner</th>
                    <th>Description</th>
                    <th>Private</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@section('controller')
    public function getGithub()
    {
        return view('datatables.collection.github');
    }

    public function getGithubData()
    {
        $search = $request->get('search');
        $keyword = $search['value']?: 'laravel';
        $repositories = \Cache::get($keyword, function() use($keyword) {
            $client = new \GuzzleHttp\Client();
            $response = $client->get('https://api.github.com/search/repositories', [
                    'query' => ['q' => $keyword]
                ]);
            $repositories = $response->json();
            \Cache::put($keyword, $repositories, 1);

            return $repositories;
        });

        $data = new Collection($repositories['items']);

        return Datatables::of($data)
            ->editColumn('full_name', function($row) {
                return \HTML::link($row['url'], $row['full_name']);
            })
            ->editColumn('private', function($row) {
                return $row['private'] ? 'Y' : 'N';
            })
            ->filter(function(){}) // disable built-in search function
            ->make(true);
    }
@endsection

@section('js')
    // add plugin http://datatables.net/plug-ins/api/fnFilterOnReturn
    jQuery.fn.dataTableExt.oApi.fnFilterOnReturn = function (oSettings) {
        var _that = this;

        this.each(function (i) {
            $.fn.dataTableExt.iApiIndex = i;
            var $this = this;
            var anControl = $('input', _that.fnSettings().aanFeatures.f);
            anControl
                .unbind('keyup search input')
                .bind('keypress', function (e) {
                    if (e.which == 13) {
                        $.fn.dataTableExt.iApiIndex = i;
                        _that.fnFilter(anControl.val());
                    }
                });
            return this;
        });
        return this;
    };

    $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ url("collection/github-data") }}',
        columns: [
            {data: 'stargazers_count', name: 'stargazers_count'},
            {data: 'full_name', name: 'full_name'},
            {data: 'owner.login', name: 'owner.login', orderable: false, searchable: false},
            {data: 'description', name: 'description'},
            {data: 'private', name: 'private'}
        ],
        order: [[0, 'desc']]
    });

    // submit search on return
    $('#datatable').dataTable().fnFilterOnReturn();
@endsection