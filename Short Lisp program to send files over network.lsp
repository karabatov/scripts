; sfsock.lsp
; © 2010 Yuri Karabatov

; Usage: sfsock.lsp server_ip port_index file_name

(if-not (> (length (main-args)) 3) ((println {Usage: sfsock.exe "MOXA IP address" "COM port number" "file to send"})(exit)))

(print "Set arguments... ")
(set 'file_name (main-args -1))
(if-not (file? file_name) ((println "Check file name!")(exit)))
(set 'port_index (main-args -2))
(if-not (> port_index 0) ((println "Check port number!")(exit)))
(set 'server_ip (main-args -3))
(if-not (= (length (find-all {[.]} server_ip)) 3) ((println "Check MOXA IP!")(exit)))
(println "OK.")

(print "Open socket... ")
(set 'sock (net-connect server_ip (+ 4000 (int port_index))))
(if-not sock ((println "Open socket failed!")(exit)))
(println "OK.")

(set 'font_file (open file_name "read"))
(set 'buf "")
(print "Sending data...")
(while (read-buffer font_file buf 255)(if (net-send sock buf) (print ".")((print "Failed! Quitting.")(close font_file)(net-close sock)(exit))))   
(println " OK.")
(print "Close file... ")
(if (close font_file) (println "OK.")(println "Close file failed!"))
(print "Close socket... ")
(if (net-close sock true) (println "OK.")(println "Close socket failed!"))
(println "Quit.")
(exit)

;; eof
