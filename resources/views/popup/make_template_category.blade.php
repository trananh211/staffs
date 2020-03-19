@extends('popup')
@section('content')
    <div class="row">
        <div class="col s12 m12 l12">
            <div class="card">
                <div class="card-content">
                    <div class="container">
                        <span class="card-title">Upload Image </span>
                        <div>
                            <ol class='example'>
                                <li>First</li>
                                <li>Second</li>
                                <li>Third</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <script type="text/javascript">
        $(function  () {
            $("ol.example").sortable();
        });
    </script>
@endsection

