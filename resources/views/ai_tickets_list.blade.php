<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Live Tickets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <h2 class="mb-4 text-primary fw-bold text-center">🤖 এআই লাইভ টিকিট লিস্ট (নতুন ডাটাবেস)</h2>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-bordered table-striped text-center">
                <thead class="table-dark">
                    <tr>
                        <th>#ID</th>
                        <th>কাস্টমারের নাম</th>
                        <th>মোবাইল নাম্বার</th>
                        <th>ঠিকানা</th>
                        <th>পণ্যের নাম</th>
                        <th>সমস্যার বিবরণ</th>
                        <th>স্ট্যাটাস</th>
                        <th>সময়</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tickets as $ticket)
                    <tr>
                        <td>{{ $ticket->id }}</td>
                        <td>{{ $ticket->customer_name }}</td>
                        <td>{{ $ticket->mobile_number }}</td>
                        <td>{{ $ticket->address }}</td>
                        <td>{{ $ticket->product_name }}</td>
                        <td>{{ $ticket->problem_description }}</td>
                        <td><span class="badge bg-warning text-dark">{{ $ticket->status }}</span></td>
                        <td>{{ $ticket->created_at->format('d M Y, h:i A') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            @if($tickets->isEmpty())
                <p class="text-center text-danger mt-3">এখনো কোনো টিকিট আসেনি। লাইভ কল করে টেস্ট করুন!</p>
            @endif
        </div>
    </div>
</div>

</body>
</html>