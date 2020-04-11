<h4>File Fulfill Job: {{ $number_order }}</h4>
<table>
    <thead>
    <tr>
        <th>#</th>
        <th>Thumb</th>
        <th>Item Name</th>
        <th>Action</th>
    </tr>
    </thead>
    <tbody>
    <?php $i = 1; ?>
    @foreach ($check as $item)
        <tr>
            <td>{{ $i++ }}</td>
            <td><img class="thumb" src="{{ env('URL_LOCAL').$item->thumb }}" alt=""></td>
            <td>{{ basename($item->web_path_file) }}</td>
            <td>
                <a href="{{ env('URL_LOCAL').$item->web_path_file }}" target="_blank">View Image</a>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
<style>
    table tr, table td, table th {
        padding: 20px;
        text-align: center;
        border: 1px solid black;
    }
    .thumb {
        width: 200px;
        height: auto;
    }
</style>
