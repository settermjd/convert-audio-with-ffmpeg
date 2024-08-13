ARG ALPINE_VERSION=3.20
ARG PHP_VERSION=8.3.9

FROM php:${PHP_VERSION}-fpm-alpine${ALPINE_VERSION} AS base

COPY --from=composer:2.3.5 /usr/bin/composer /usr/bin/

RUN apk --update-cache add \
    aom-dev \
    autoconf \
    automake \
    dav1d-dev \
    g++ \
    gcc \
    git \
    gnutls \
    gnutls-dev \
    gnutls-utils \
    make \
    lame-dev \
    libass-dev \
    libtool \
    libunistring-dev \
    libvorbis-dev \
    libvpx-dev \
    opus-dev \
    svt-av1-dev \
    x264-dev

RUN git -C fdk-aac pull 2> /dev/null || git clone --depth 1 https://github.com/mstorsjo/fdk-aac && \
  cd fdk-aac && \
  autoreconf -fiv && \
  ./configure --prefix="$HOME/ffmpeg_build" --disable-shared && \
  make && \
  make install

RUN wget -O ffmpeg-snapshot.tar.bz2 https://ffmpeg.org/releases/ffmpeg-snapshot.tar.bz2 && \
    tar xjvf ffmpeg-snapshot.tar.bz2 && \
    cd ffmpeg && \
    PATH="$HOME/bin:$PATH" PKG_CONFIG_PATH="$HOME/ffmpeg_build/lib/pkgconfig" ./configure \
      --prefix="$HOME/ffmpeg_build" \
      --pkg-config-flags="--static" \
      --extra-cflags="-I$HOME/ffmpeg_build/include" \
      --extra-ldflags="-L$HOME/ffmpeg_build/lib" \
      --extra-libs="-lpthread -lm" \
      --ld="g++" \
      --bindir="/usr/local/bin" \
      --enable-gpl \
      --enable-gnutls \
      --enable-libaom \
      --enable-libass \
      --enable-libfdk-aac \
      --enable-libfreetype \
      --enable-libmp3lame \
      --enable-libopus \
      --enable-libsvtav1 \
      --enable-libdav1d \
      --enable-libvorbis \
      --enable-libvpx \
      --enable-libx264 \
      --enable-nonfree && \
    PATH="$HOME/bin:$PATH" make && \
    make install && \
    hash -r