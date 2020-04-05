<header class="mn-header navbar-fixed">
    <nav class="cyan darken-1">
        <div class="nav-wrapper row">
            <section class="material-design-hamburger navigation-toggle">
                <a href="javascript:void(0)" data-activates="slide-out"
                   class="button-collapse show-on-large material-design-hamburger__icon">
                    <span class="material-design-hamburger__layer"></span>
                </a>
            </section>
            <div class="header-title col s3 m3">
                <span class="chapter-title">Alpha</span>
            </div>
            @if( in_array( Auth::user()->level, array( env('ADMIN'),env('QC'),env('SADMIN')) ) )
            <form action="{{ url('search-work-job') }}" method="post" class="left search col s6 hide-on-small-and-down">
                    {{ csrf_field() }}
                <div class="input-field">
                    <input value="{{ Session::has('search')? Session('search.keyword') : '' }}"
                        id="search-job" type="search" placeholder="Search" autocomplete="off" name="search_job">
                    <label for="search-job"><i class="material-icons search-icon">search</i></label>
                </div>
                <a href="javascript: void(0)" class="close-search"><i class="material-icons">close</i></a>
            </form>
            @endif
            <ul class="right col s9 m3 nav-right-menu">
                <li><a href="javascript:void(0)" data-activates="chat-sidebar" class="chat-button show-on-large"><i
                            class="material-icons">more_vert</i></a></li>
                <li class="hide-on-small-and-down"><a href="javascript:void(0)" data-activates="dropdown1"
                                                      class="dropdown-button dropdown-right show-on-large"><i
                            class="material-icons">notifications_none</i><span class="badge">4</span></a></li>
                <li class="hide-on-med-and-up"><a href="javascript:void(0)" class="search-toggle"><i
                            class="material-icons">search</i></a></li>
            </ul>

            <ul id="dropdown1" class="dropdown-content notifications-dropdown">
                <li class="notificatoins-dropdown-container">
                    <ul>
                        <li class="notification-drop-title">Today</li>
                        <li>
                            <a href="#!">
                                <div class="notification">
                                    <div class="notification-icon circle cyan"><i class="material-icons">done</i></div>
                                    <div class="notification-text"><p><b>Alan Grey</b> uploaded new theme</p><span>7 min ago</span>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="#!">
                                <div class="notification">
                                    <div class="notification-icon circle deep-purple"><i
                                            class="material-icons">cached</i></div>
                                    <div class="notification-text"><p><b>Tom</b> updated status</p>
                                        <span>14 min ago</span></div>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="#!">
                                <div class="notification">
                                    <div class="notification-icon circle red"><i class="material-icons">delete</i></div>
                                    <div class="notification-text"><p><b>Amily Lee</b> deleted account</p><span>28 min ago</span>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="#!">
                                <div class="notification">
                                    <div class="notification-icon circle cyan"><i class="material-icons">person_add</i>
                                    </div>
                                    <div class="notification-text"><p><b>Tom Simpson</b> registered</p>
                                        <span>2 hrs ago</span></div>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="#!">
                                <div class="notification">
                                    <div class="notification-icon circle green"><i
                                            class="material-icons">file_upload</i></div>
                                    <div class="notification-text"><p>Finished uploading files</p><span>4 hrs ago</span>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li class="notification-drop-title">Yestarday</li>
                        <li>
                            <a href="#!">
                                <div class="notification">
                                    <div class="notification-icon circle green"><i class="material-icons">security</i>
                                    </div>
                                    <div class="notification-text"><p>Security issues fixed</p><span>16 hrs ago</span>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="#!">
                                <div class="notification">
                                    <div class="notification-icon circle indigo"><i
                                            class="material-icons">file_download</i></div>
                                    <div class="notification-text"><p>Finished downloading files</p>
                                        <span>22 hrs ago</span></div>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="#!">
                                <div class="notification">
                                    <div class="notification-icon circle cyan"><i class="material-icons">code</i></div>
                                    <div class="notification-text"><p>Code changes were saved</p><span>1 day ago</span>
                                    </div>
                                </div>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
</header>
