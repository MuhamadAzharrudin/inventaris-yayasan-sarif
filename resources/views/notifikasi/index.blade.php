@extends('layouts.admin')

@section('page-title', 'Notifikasi')
@section('page-icon', 'bell')

@section('content')

<div class="page-head">
    <div>
        <h1><i data-lucide="bell"></i> Notifikasi</h1>
        <p>Pemberitahuan pelaporan, verifikasi, dan mutasi barang untuk akun Anda</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('notifikasi.index') }}" class="btn {{ $filter === 'all' ? 'btn-primary' : 'btn-ghost' }}">
            Semua
        </a>
        <a href="{{ route('notifikasi.index', ['filter' => 'unread']) }}" class="btn {{ $filter === 'unread' ? 'btn-primary' : 'btn-ghost' }}">
            Belum dibaca @if($unreadCount > 0)<span class="badge badge-red">{{ $unreadCount }}</span>@endif
        </a>
        @if($unreadCount > 0)
            <form action="{{ route('notifikasi.readAll') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-green"><i data-lucide="check-check"></i> Tandai semua dibaca</button>
            </form>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-head">
        <h3><i data-lucide="inbox"></i> Daftar Notifikasi</h3>
        <span class="muted">{{ $notifications->total() }} notifikasi · {{ $unreadCount }} belum dibaca</span>
    </div>

    <div>
        @forelse($notifications as $notif)
            <div style="display:flex;gap:13px;padding:15px 18px;border-bottom:1px solid #F1F5F9;border-left:4px solid {{ $notif->isUnread() ? $notif->accentColor() : 'transparent' }};background:{{ $notif->isUnread() ? '#F8FBFF' : '#fff' }};">
                <span style="width:38px;height:38px;border-radius:11px;flex-shrink:0;display:flex;align-items:center;justify-content:center;color:#fff;background:{{ $notif->accentColor() }};">
                    <i data-lucide="{{ $notif->iconName() }}"></i>
                </span>

                <div style="min-width:0;flex:1;">
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                        <span style="font-weight:800;font-size:.88rem;">{{ $notif->judul }}</span>
                        @if($notif->isUnread())
                            <span class="badge badge-blue">Baru</span>
                        @endif
                    </div>
                    <div style="font-size:.83rem;color:var(--gray);margin-top:3px;">{{ $notif->pesan }}</div>
                    <div style="font-size:.73rem;color:var(--gray-l);margin-top:5px;display:flex;gap:10px;flex-wrap:wrap;">
                        <span><i data-lucide="clock" style="width:11px;height:11px;"></i> {{ $notif->created_at?->diffForHumans() }}</span>
                        <span>{{ $notif->created_at?->format('d M Y H:i') }}</span>
                        @if($notif->unit)<span class="badge badge-gray">{{ $notif->unit->label() }}</span>@endif
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:6px;align-items:flex-end;">
                    @if($notif->url)
                        <form action="{{ route('notifikasi.read', $notif->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i data-lucide="arrow-right" style="width:13px;height:13px;"></i> Buka
                            </button>
                        </form>
                    @elseif($notif->isUnread())
                        <form action="{{ route('notifikasi.read', $notif->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-sm">
                                <i data-lucide="check" style="width:13px;height:13px;"></i> Tandai dibaca
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="empty-state" style="padding:60px 20px;">
                <i data-lucide="bell-off" style="width:40px;height:40px;stroke-width:1.4;"></i>
                <strong>Tidak ada notifikasi</strong>
                <p>
                    @if($filter === 'unread')
                        Semua notifikasi Anda sudah dibaca.
                    @else
                        Notifikasi akan muncul saat ada laporan baru, verifikasi Yayasan, atau mutasi barang.
                    @endif
                </p>
            </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div class="card-pad pagination-wrap">{{ $notifications->links() }}</div>
    @endif
</div>

@endsection
