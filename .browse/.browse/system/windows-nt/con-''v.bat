FOR %%A IN (*.??v) DO ( ffmpeg -fflags +genpts -i "%%A" -vcodec copy -acodec copy "%%A.mp4" )

